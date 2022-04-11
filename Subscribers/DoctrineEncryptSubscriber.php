<?php

namespace SpecShaper\EncryptBundle\Subscribers;

use Doctrine\Common\Annotations\Reader;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use ReflectionProperty;
use SpecShaper\EncryptBundle\Annotations\Encrypted;
use SpecShaper\EncryptBundle\Encryptors\EncryptorInterface;
use SpecShaper\EncryptBundle\Exception\EncryptException;

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
     * Encryptor.
     */
    protected EncryptorInterface $encryptor;

    /**
     * Annotation reader.
     */
    protected Reader $annReader;

    /**
     * Registr to avoid multi decode operations for one entity.
     */
    private array $decodedRegistry = [];

    /**
     * An array of decoded values populated during the onLoad event.
     * Used to compare any resubmitted values during onFlush event.
     * If the flushed unencoded value is the same as in the array then there is no change
     * to the value and the entity field update is removed from the Unit of Work change set.
     */
    private array $decodedValues = [];

    /**
     * Caches information on an entity's encrypted fields in an array keyed on
     * the entity's class name. The value will be a list of Reflected fields that are encrypted.
     */
    protected array $encryptedFieldCache = [];

    /**
     * Remember which entities have to be decrypted back in postFlush after onFlush.
     */
    private array $postFlushDecryptQueue = [];

    private bool $isDisabled;

    public function __construct(Reader $annReader, EncryptorInterface $encryptor, array $annotationArray, bool $isDisabled)
    {
        $this->annReader = $annReader;
        $this->encryptor = $encryptor;
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
            Events::onFlush,
            Events::postFlush,
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

        $em = $args->getEntityManager();
        $unitOfWork = $em->getUnitOfWork();

        $this->postFlushDecryptQueue = [];

        foreach ($unitOfWork->getScheduledEntityInsertions() as $entity) {
            $this->entityOnFlush($entity, $em);
        }

        foreach ($unitOfWork->getScheduledEntityUpdates() as $entity) {
            $this->entityOnFlush($entity, $em);
            $unitOfWork->recomputeSingleEntityChangeSet($em->getClassMetadata(get_class($entity)), $entity);
        }
    }

    /**
     * Processes the entity for an onFlush event.
     *
     * @param $entity
     * @param EntityManagerInterface $em
     * @throws EncryptException
     */
    protected function entityOnFlush($entity, EntityManagerInterface $em): void
    {
        // If encryption is disabled return void.
        if ($this->isDisabled) {
            return;
        }

        // Add the entity to a decrypt Queue for postFlush decryption.
        $this->postFlushDecryptQueue[] = $entity;

        // Encrypt entity fields.
        $this->processFields($entity, $em, true);
    }

    /**
     * After we have persisted the entities, we want to have the
     * decrypted information available once more.
     */
    public function postFlush(PostFlushEventArgs $args): void
    {
        if ($this->isDisabled) {
            return;
        }

        foreach ($this->postFlushDecryptQueue as $entity) {
            $hasFieldsEncrypted = $this->processFields($entity, $args->getEntityManager(), false);

            // If no fields were marked encrypted then skip.
            if (false === $hasFieldsEncrypted) {
                continue;
            }

            $this->addToDecodedRegistry($entity);
        }

        $this->postFlushDecryptQueue = [];
    }

    /**
     * Listen a postLoad lifecycle event. Checking and decrypt entities
     * which have @Encrypted annotations.
     *
     * @throws EncryptException
     */
    public function postLoad(LifecycleEventArgs $args): void
    {
        $entity = $args->getEntity();
        $em = $args->getEntityManager();

        // If this entity has already been decoded in an earlier postFlush event then do nothing.
        if ($this->hasInDecodedRegistry($entity)) {
            return;
        }

        // Decrypt the entity fields.
        $hasFieldsEncrypted = $this->processFields($entity, $args->getEntityManager(), false);

        // If the entity contained encrypted fields that were decrypted then add to a registry.
        if ($hasFieldsEncrypted) {
            $this->addToDecodedRegistry($entity);
        }
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
            /** @var ReflectionProperty $refProperty */
            foreach ($this->annReader->getPropertyAnnotations($refProperty) as $key => $annotation) {
                if (in_array(get_class($annotation), $this->annotationArray)) {
                    $refProperty->setAccessible(true);
                    $encryptedFields[] = $refProperty;
                }
            }
        }

        return $encryptedFields;
    }

    /**
     * Process (encrypt/decrypt) entities fields.
     */
    protected function processFields(object $entity, EntityManagerInterface $em, ?bool $isEncryptOperation = true): bool
    {
        // Get the encrypted properties in the entity.
        $properties = $this->getEncryptedFields($entity, $em);

        // If no encrypted properties, return false.
        if (empty($properties)) {
            return false;
        }

        $unitOfWork = $em->getUnitOfWork();
        $oid = spl_object_id($entity);

        if(!array_key_exists($oid, $this->decodedValues)){
            $this->decodedValues[$oid] = [];
        }

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

                // If the field has already been decrypted by the onLoad event, and the flushed value is the same
                if(isset($this->decodedValues[$oid][$refProperty->getName()]) && $this->decodedValues[$oid][$refProperty->getName()][1] === $value){

                    // Remove the field from the UoW change set.
                    unset($unitOfWork->getEntityChangeSet($entity)[$refProperty->getName()]);

                    // Get the originally created encrypted value.
                    $encryptedValue = $this->decodedValues[$oid][$refProperty->getName()][0];

                    // Reset that to the original in the UoW.
                    $unitOfWork->setOriginalEntityProperty($oid, $refProperty->getName(), $encryptedValue);
                } else {
                    // The field is part of an insert or the value of the field has changed, then create a new encrypted value.
                    $encryptedValue = $this->encryptor->encrypt($value);
                }

                // Replace the unencrypted value with the encrypted value on the entity.
                $refProperty->setValue($entity, $encryptedValue);

            } else {
                // Decryption is fired by onLoad and postFlush events.
                $decryptedValue = $this->decryptValue($value);
                $refProperty->setValue($entity, $decryptedValue);

                // Store the decrypted value for comparison during a flush event.
                $this->decodedValues[$oid][$refProperty->getName()] = [$value, $decryptedValue];

                // We don't want the object to be dirty immediately after reading
                $unitOfWork->setOriginalEntityProperty($oid, $refProperty->getName(), $value);
            }
        }

        return !empty($properties);
    }

    /**
     * Check if we have entity in decoded registry.
     *
     * @param object $entity Some doctrine entity
     */
    protected function hasInDecodedRegistry(object $entity): bool
    {
        return isset($this->decodedRegistry[spl_object_id($entity)]);
    }

    /**
     * Adds entity to decoded registry.
     *
     * @param object $entity Some doctrine entity
     */
    protected function addToDecodedRegistry(object $entity): void
    {
        $this->decodedRegistry[spl_object_id($entity)] = true;
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
            /** @var ReflectionProperty $refProperty */
            foreach ($this->annReader->getPropertyAnnotations($refProperty) as $key => $annotation) {
                if (in_array(get_class($annotation), $this->annotationArray)) {
                    $refProperty->setAccessible(true);
                    $encryptedFields[] = $refProperty;
                }
            }
        }

        $this->encryptedFieldCache[$className] = $encryptedFields;

        return $encryptedFields;
    }
}
