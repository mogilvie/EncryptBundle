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
use SpecShaper\EncryptBundle\Exception\EncryptException;

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
     * Remember which entities have to be decrypted back in postFlush after onFlush.
     *
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
     * Realization of EventSubscriber interface method.
     * @return array Return all events which this subscriber is listening
     */
    public function getSubscribedEvents()
    {
        return array(
            Events::postLoad,
            Events::onFlush,
            Events::postFlush,
        );
    }

    /**
     * Encrypt the password before it is written to the database.
     *
     * Notice that we do not recalculate changes otherwise the password will be written
     * every time (Because it is going to differ from the un-encrypted value)
     *
     * @param OnFlushEventArgs $args
     * @throws EncryptException
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
     * @param               $entity
     * @param EntityManager $em
     * @throws EncryptException
     */
    protected function entityOnFlush($entity, EntityManager $em)
    {
        if($this->isDisabled){
            return;
        }

        $this->postFlushDecryptQueue[] = $entity;
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

        foreach ($this->postFlushDecryptQueue as $entity) {
            $this->processFields($entity, $args->getEntityManager(), false);
            $this->addToDecodedRegistry($entity);
        }

        $this->postFlushDecryptQueue = array();
    }

    /**
     * Listen a postLoad lifecycle event. Checking and decrypt entities
     * which have @Encrypted annotations
     *
     * @param LifecycleEventArgs $args
     * @throws EncryptException
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

        // Else decrypt value and return.
        $decrypted = $this->encryptor->decrypt($value);

        return $decrypted;
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
     * @param               $entity
     * @param EntityManager $em
     * @param bool          $isEncryptOperation
     * @return bool
     * @throws EncryptException
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

            if (is_object($value)) {
                throw new EncryptException('You cannot encrypt an object at ' . $refProperty->class .':'.  $refProperty->getName() , $value);
            }

            // If the required operation is to encrypt then encrypt the value.
            if($isEncryptOperation) {
                $encryptedValue = $this->encryptor->encrypt($value);
                $refProperty->setValue($entity, $encryptedValue);
            } else {
                $decryptedValue = $this->decryptValue($value);
                $refProperty->setValue($entity, $decryptedValue);
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
