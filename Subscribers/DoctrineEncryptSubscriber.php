<?php

namespace SpecShaper\EncryptBundle\Subscribers;

use Doctrine\Common\Annotations\Reader;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use ReflectionProperty;
use SpecShaper\EncryptBundle\Annotations\Encrypted;
use SpecShaper\EncryptBundle\Encryptors\EncryptorInterface;
use SpecShaper\EncryptBundle\Exception\EncryptException;
use Psr\Log\LoggerInterface;


/**
 * Doctrine event subscriber which encrypt/decrypt entities.
 */
class DoctrineEncryptSubscriber implements EventSubscriber, DoctrineEncryptSubscriberInterface
{
    /**
     * Encryptor interface namespace.
     */
    public const ENCRYPTOR_INTERFACE_NS = EncryptorInterface::class;

    /**
     * An array of annotations which are to be encrypted.
     * The default and initial is the bundle Encrypted Class.
     */
    protected array $annotationArray;

    /**
     * Caches information on an entity's encrypted fields in an array keyed on
     * the entity's class name. The value will be a list of Reflected fields that are encrypted.
     */
    protected array $encryptedFieldCache = [];

    private array $rawValues = [];

    private bool $isDisabled;

    public function __construct(
        private LoggerInterface $logger,
        private Reader $annReader,
        private EncryptorInterface $encryptor,
        array $annotationArray,
        bool $isDisabled
    ) {
        $this->annotationArray = $annotationArray;
        $this->isDisabled = $isDisabled;
    }

    public function getEncryptor(): EncryptorInterface
    {
        return $this->encryptor;
    }

    /**
     * Set Is Disabled.
     *
     * Used to programmatically disable encryption on flush operations.
     * Decryption still occurs if values have the <ENC> suffix.
     */
    public function setIsDisabled(?bool $isDisabled = true): DoctrineEncryptSubscriberInterface
    {
        $this->isDisabled = $isDisabled;

        return $this;
    }

    /**
     * Realization of EventSubscriber interface method.
     *
     * @return array Return all events which this subscriber is listening
     */
    public function getSubscribedEvents(): array
    {
        return [
            Events::postLoad,
            Events::postUpdate,
            Events::onFlush,
        ];
    }

    /**
     * @throws EncryptException
     */
    public function onFlush(OnFlushEventArgs $args): void
    {
        if ($this->isDisabled) {
            return;
        }

        $em = $args->getObjectManager();
        $unitOfWork = $em->getUnitOfWork();

        foreach ($unitOfWork->getScheduledEntityInsertions() as $entity) {
            $this->processFields($entity, $em, true, true);
        }

        foreach ($unitOfWork->getScheduledEntityUpdates() as $entity) {
            $this->processFields($entity, $em, true, false);
        }
    }

    /**
     * Listen a postLoad lifecycle event. Checking and decrypt entities
     * which have @Encrypted annotations.
     *
     * @throws EncryptException
     */
    public function postLoad(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();
        // Decrypt the entity fields.
        $this->processFields($entity, $args->getObjectManager(), false, false);
    }

    /**
     * Decrypt a value.
     *
     * If the value is an object, or if it does not contain the suffic <ENC> then return the value iteslf back.
     * Otherwise, decrypt the value and return.
     */
    public function decryptValue(?string $value): ?string
    {
        // Else decrypt value and return.
        return $this->encryptor->decrypt($value);
    }

    public function getEncryptionableProperties(array $allProperties): array
    {
        $encryptedFields = [];

        foreach ($allProperties as $refProperty) {
            if ($this->isEncryptedProperty($refProperty)) {
                $encryptedFields[] = $refProperty;
            }
        }

        return $encryptedFields;
    }

    /**
     * Process (encrypt/decrypt) entities fields.
     */
    protected function processFields(object $entity, EntityManagerInterface $em, bool $isEncryptOperation, bool $isInsert): bool
    {
        // Get the encrypted properties in the entity.
        $properties = $this->getEncryptedFields($entity, $em);

        // If no encrypted properties, return false.
        if (empty($properties)) {
            return false;
        }

        $unitOfWork = $em->getUnitOfWork();
        $oid = spl_object_id($entity);

        foreach ($properties as $key => $refProperty) {
            // Get the value in the entity.
            $value = $refProperty->getValue($entity);

            // Skip any null values.
            if (null === $value) {
                continue;
            }

            if (is_object($value)) {
                throw new EncryptException('Cannot encrypt an object at '.$refProperty->class.':'.$refProperty->getName(), $value);
            }

            // Encryption is fired by onFlush event, else it is an onLoad event.
            if ($isEncryptOperation) {
                $changeSet = $unitOfWork->getEntityChangeSet($entity);

                // Encrypt value only if change has been detected by Doctrine (comparing unencrypted values, see postLoad flow)
                if (isset($changeSet[$refProperty->getName()])) {
                    $encryptedValue = $this->encryptor->encrypt($value);
                    $refProperty->setValue($entity, $encryptedValue);
                    $unitOfWork->recomputeSingleEntityChangeSet($em->getClassMetadata(get_class($entity)), $entity);

                    if ($isInsert) {
                        // Restore the decrypted value after the change set update
                        $refProperty->setValue($entity, $value);
                    } else {
                        // Will be restored during postUpdate cycle
                        $this->rawValues[$oid][$refProperty->getName()] = $value;
                    }
                }
            } else {
                // Decryption is fired by onLoad and postFlush events.
                $decryptedValue = $this->decryptValue($value);
                $refProperty->setValue($entity, $decryptedValue);

                // Tell Doctrine the original value was the decrypted one.
                $unitOfWork->setOriginalEntityProperty($oid, $refProperty->getName(), $decryptedValue);
            }
        }

        return !empty($properties);
    }

    public function postUpdate(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();
        $em = $args->getObjectManager();

        $oid = spl_object_id($entity);
        if (isset($this->rawValues[$oid])) {
            $className = get_class($entity);
            $meta = $em->getClassMetadata($className);
            foreach ($this->rawValues[$oid] as $prop => $rawValue) {
                $refProperty = $meta->getReflectionProperty($prop);
                $refProperty->setValue($entity, $rawValue);
            }

            unset($this->rawValues[$oid]);
        }
    }

    /**
     * @return ReflectionProperty[]
     */
    protected function getEncryptedFields(object $entity, EntityManagerInterface $em): array
    {
        $className = get_class($entity);

        if (isset($this->encryptedFieldCache[$className])) {
            return $this->encryptedFieldCache[$className];
        }

        $meta = $em->getClassMetadata($className);

        $encryptedFields = [];

        foreach ($meta->getReflectionProperties() as $refProperty) {
            if ($this->isEncryptedProperty($refProperty)) {
                $encryptedFields[] = $refProperty;
            }
        }

        $this->encryptedFieldCache[$className] = $encryptedFields;

        return $encryptedFields;
    }

    private function isEncryptedProperty(ReflectionProperty $refProperty)
    {
        foreach ($refProperty->getAttributes() as $refAttribute) {
            if (in_array($refAttribute->getName(), $this->annotationArray)) {
                return true;
            }
        }

        foreach ($this->annReader->getPropertyAnnotations($refProperty) as $key => $annotation) {
            if (in_array(get_class($annotation), $this->annotationArray)) {
                $refProperty->setAccessible(true);

                $this->logger->debug(sprintf('Use of @Encrypted property from SpecShaper/EncryptBundle in property %s is deprectated.
                    Please use #[Encrypted] attribute instead.',
                    $refProperty
                ));

                return true;
            }
        }

        return false;
    }
}
