<?php

namespace SpecShaper\EncryptBundle\Subscribers;

use Doctrine\Common\Annotations\Reader;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Events;
use SpecShaper\EncryptBundle\Encryptors\EncryptorInterface;
use SpecShaper\EncryptBundle\Annotations\Encrypted;
use SpecShaper\EncryptBundle\Event\EncryptEvent;
use SpecShaper\EncryptBundle\Event\EncryptEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Doctrine event subscriber which encrypt/decrypt entities
 */
class DoctrineEncryptSubscriber implements EventSubscriber, DoctrineEncryptSubscriberInterface
{
    /**
     * Encryptor interface namespace
     */
    const ENCRYPTOR_INTERFACE_NS = EncryptorInterface::class;

    /**
     * An array of annotations which are to be encrypted.
     * The default and initial is the bundle Encrypted Class.
     * @var array
     */
    protected $annotationArray;

    /**
     * Encryptor
     * @var EncryptorInterface
     */
    protected $encryptor;

    /**
     * Annotation reader
     * @var \Doctrine\Common\Annotations\Reader
     */
    protected $annReader;

    /**
     * Registr to avoid multi decode operations for one entity
     * @var array
     */
    private $decodedRegistry = array();

    /**
     * Caches information on an entity's encrypted fields in an array keyed on
     * the entity's class name. The value will be a list of Reflected fields that are encrypted.
     *
     * @var array
     */
    protected $encryptedFieldCache = array();

    /**
     * Before flushing the objects out to the database, we modify their password value to the
     * encrypted value. Since we want the password to remain decrypted on the entity after a flush,
     * we have to write the decrypted value back to the entity.
     * @var array
     */
    private $postFlushDecryptQueue = array();

    private $isDisabled;

    /**
     * @param \Doctrine\Common\Annotations\Reader                     $annReader
     * @param \SpecShaper\EncryptBundle\Encryptors\EncryptorInterface $encryptor
     * @param                                                         $annotationArray
     * @param                                                         $isDisabled
     */
    public function __construct(Reader $annReader, EncryptorInterface $encryptor, $annotationArray, $isDisabled)
    {
        $this->annReader = $annReader;
        $this->encryptor = $encryptor;
        $this->annotationArray = $annotationArray;
        $this->isDisabled = $isDisabled;

    }

    /**
     * Return the encryptor.
     *
     * @return \SpecShaper\EncryptBundle\Encryptors\EncryptorInterface
     */
    public function getEncryptor()
    {
        return $this->encryptor;
    }

    /**
     * Set Is Disabled
     *
     * Used to programmatically disable encryption on flush operations.
     * Decryption still occurs if values have the <ENC> suffix.
     *
     * @param bool $isDisabled
     *
     * @return $this
     */
    public function setIsDisabled($isDisabled = true){
        $this->isDisabled = $isDisabled;

        return $this;
    }

    /**
     * Encrypt the password before it is written to the database.
     *
     * Notice that we do not recalculate changes otherwise the password will be written
     * every time (Because it is going to differ from the un-encrypted value)
     *
     * @param OnFlushEventArgs $args
     */
    public function onFlush(OnFlushEventArgs $args)
    {
        if($this->isDisabled){
            return;
        }

        $em = $args->getEntityManager();
        $unitOfWork = $em->getUnitOfWork();

        $this->postFlushDecryptQueue = array();

        foreach ($unitOfWork->getScheduledEntityInsertions() as $entity) {
            $this->entityOnFlush($entity, $em);
            $unitOfWork->recomputeSingleEntityChangeSet($em->getClassMetadata(get_class($entity)), $entity);
        }

        foreach ($unitOfWork->getScheduledEntityUpdates() as $entity) {
            $this->entityOnFlush($entity, $em);
            $unitOfWork->recomputeSingleEntityChangeSet($em->getClassMetadata(get_class($entity)), $entity);
        }
    }


    /**
     * Processes the entity for an onFlush event.
     *
     * @param object $entity
     * @param EntityManager $em
     */
    protected function entityOnFlush($entity, EntityManager $em)
    {
        if($this->isDisabled){
            return;
        }

        $objId = spl_object_hash($entity);

        $fields = array();
        foreach ($this->getEncryptedFields($entity, $em) as $field) {
            $fields[$field->getName()] = array(
                'field' => $field,
                'value' => $field->getValue($entity),
            );
        }

        $this->postFlushDecryptQueue[$objId] = array(
            'entity' => $entity,
            'fields' => $fields,
        );

        $this->processFields($entity, $em);
    }

    /**
     * After we have persisted the entities, we want to have the
     * decrypted information available once more.
     * @param PostFlushEventArgs $args
     */
    public function postFlush(PostFlushEventArgs $args)
    {
        if($this->isDisabled){
            return;
        }

        $unitOfWork = $args->getEntityManager()->getUnitOfWork();

        foreach ($this->postFlushDecryptQueue as $pair) {
            $fieldPairs = $pair['fields'];
            $entity = $pair['entity'];
            $oid = spl_object_hash($entity);

            foreach ($fieldPairs as $fieldPair) {
                /** @var \ReflectionProperty $field */
                $field = $fieldPair['field'];

                $field->setValue($entity, $fieldPair['value']);
                $unitOfWork->setOriginalEntityProperty($oid, $field->getName(), $fieldPair['value']);
            }

            $this->addToDecodedRegistry($entity);
        }

        $this->postFlushDecryptQueue = array();
    }

    /**
     * Listen a postLoad lifecycle event. Checking and decrypt entities
     * which have @Encrypted annotations
     * @param LifecycleEventArgs $args
     */
    public function postLoad(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();
        $em = $args->getEntityManager();

        if (!$this->hasInDecodedRegistry($entity)) {
            if ($this->processFields($entity, $em, false)) {
                $this->addToDecodedRegistry($entity);
            }
        }
    }

    /**
     * Realization of EventSubscriber interface method.
     * @return array Return all events which this subscriber is listening
     */
    public function getSubscribedEvents()
    {
        return array(
            Events::postLoad,
            Events::onFlush,
            Events::postFlush,
            EncryptEvents::ENCRYPT,
            EncryptEvents::DECRYPT,
        );
    }

    /**
     * Capitalize string
     * @param string $word
     * @return string
     */
    public static function capitalize($word)
    {
        if (is_array($word)) {
            $word = $word[0];
        }

        return str_replace(' ', '', ucwords(str_replace(array('-', '_'), ' ', $word)));
    }

    /**
     * Use an Encrypt even to encrypt a value.
     *
     * @param \SpecShaper\EncryptBundle\Event\EncryptEvent $event
     *
     * @return string
     */
    public function encrypt(EncryptEvent $event){
        $encrypted = $this->encryptor->encrypt($event->getValue());

        $event->setValue($encrypted);

        return $encrypted;
    }

    /**
     * Use a decrypt event to decrypt a single value.
     *
     * @param \SpecShaper\EncryptBundle\Event\EncryptEvent $event
     *
     * @return string
     */
    public function decrypt(EncryptEvent $event){

        $value = $event->value();

        // If the value is an object, or does not have the suffix <ENC> then ignore.
        if($value === null || is_object($value) || substr($value, -5) != DoctrineEncryptSubscriberInterface::ENCRYPTED_SUFFIX) {
            return $value;
        }

        $decrypted = $this->encryptor->decrypt($value);

        $event->setValue($decrypted);

        return $decrypted;
    }

    /**
     * Decrypt a value.
     *
     * If the value is an object, or if it does not contain the suffic <ENC> then return the value iteslf back.
     * Otherwise, decrypt the value and return.
     *
     * @param $value
     *
     * @return string
     */
    public function decryptValue($value){

        // If the value is an object, or does not have the suffix <ENC> then ignore.
        if($value === null || is_object($value) || substr($value, -5) != DoctrineEncryptSubscriberInterface::ENCRYPTED_SUFFIX) {
            return $value;
        }

        // Else decrypt value and return.
        return $this->encryptor->decrypt(substr($value, 0, -5));

    }

    /**
     * @param $allProperties
     * @return array
     */
    public function getEncryptionableProperties($allProperties)
    {
        $encryptedFields = [];

        foreach ($allProperties as $refProperty) {
            /** @var \ReflectionProperty $refProperty */

            foreach($this->annReader->getPropertyAnnotations($refProperty) as $key => $annotation){

                if (in_array(get_class($annotation), $this->annotationArray)) {
                    $refProperty->setAccessible(true);
                    $encryptedFields[] = $refProperty;
                }
            }

        }

        return $encryptedFields;
    }

    /**
     * Process (encrypt/decrypt) entities fields
     *
     * @param                             $entity
     * @param \Doctrine\ORM\EntityManager $em
     * @param bool                        $isEncryptOperation
     *
     * @return bool
     */
    protected function processFields($entity, EntityManager $em, $isEncryptOperation = true)
    {

        $properties = $this->getEncryptedFields($entity, $em);

        $unitOfWork = $em->getUnitOfWork();
        $oid = spl_object_hash($entity);

        foreach ($properties as $refProperty) {
            $value = $refProperty->getValue($entity);

            // Skip any empty values.
            if($value === null){
                continue;
            }

            // If the required opteration is to encrypt then encrypt the value.
            if($isEncryptOperation) {
                $value = $this->encryptor->encrypt($value) . DoctrineEncryptSubscriberInterface::ENCRYPTED_SUFFIX ;
            } else {
                $value = $this->decryptValue($value);
            }

            $refProperty->setValue($entity, $value);

            if (!$isEncryptOperation) {
                //we don't want the object to be dirty immediately after reading
                $unitOfWork->setOriginalEntityProperty($oid, $refProperty->getName(), $value);
            }
        }

        return !empty($properties);
    }



    /**
     * Check if we have entity in decoded registry
     * @param object $entity Some doctrine entity
     * @return boolean
     */
    protected function hasInDecodedRegistry($entity)
    {
        return isset($this->decodedRegistry[spl_object_hash($entity)]);
    }

    /**
     * Adds entity to decoded registry
     * @param object $entity Some doctrine entity
     */
    protected function addToDecodedRegistry($entity)
    {
        $this->decodedRegistry[spl_object_hash($entity)] = true;
    }

    /**
     * @param $entity
     * @param EntityManager $em
     * @return \ReflectionProperty[]
     */
    protected function getEncryptedFields($entity, EntityManager $em)
    {

        $className = get_class($entity);

        if (isset($this->encryptedFieldCache[$className])) {
            return $this->encryptedFieldCache[$className];
        }

        $meta = $em->getClassMetadata($className);

        $encryptedFields = array();
        foreach ($meta->getReflectionProperties() as $refProperty) {
            /** @var \ReflectionProperty $refProperty */

            foreach($this->annReader->getPropertyAnnotations($refProperty) as $key => $annotation){

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
