<?php

namespace SpecShaper\EncryptBundle\Subscribers;

use Doctrine\Common\Annotations\Reader;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use SpecShaper\EncryptBundle\Encryptors\EncryptorInterface;
use SpecShaper\EncryptBundle\Annotations\Encrypted;
use SpecShaper\EncryptBundle\Event\EncryptEventInterface;

/**
 * Doctrine event subscriber which encrypt/decrypt entities
 */
interface DoctrineEncryptSubscriberInterface
{
    const ENCRYPTED_SUFFIX = "<ENC>";

    public function __construct(Reader $annReader, EncryptorInterface $encryptor, $annotationArray, $isDisabled);

    /**
     * Encrypt the password before it is written to the database.
     *
     * Notice that we do not recalculate changes otherwise the value will be written
     * every time (Because it is going to differ from the un-encrypted value)
     *
     * @param OnFlushEventArgs $args
     */
    public function onFlush(OnFlushEventArgs $args);

    /**
     * After we have persisted the entities, we want to have the
     * decrypted information available once more.
     *
     * @param PostFlushEventArgs $args
     */
    public function postFlush(PostFlushEventArgs $args);

    /**
     * Listen a postLoad lifecycle event. Checking and decrypt entities
     * which have @Encrypted annotations
     *
     * @param LifecycleEventArgs $args
     */
    public function postLoad(LifecycleEventArgs $args);

    /**
     * Realization of EventSubscriber interface method.
     * @return array Return all events which this subscriber is listening
     */
    public function getSubscribedEvents();
}
