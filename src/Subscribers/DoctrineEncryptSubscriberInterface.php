<?php

namespace SpecShaper\EncryptBundle\Subscribers;

use Doctrine\Common\Annotations\Reader;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Psr\Log\LoggerInterface;
use SpecShaper\EncryptBundle\Annotations\Encrypted;
use SpecShaper\EncryptBundle\Encryptors\EncryptorInterface;

/**
 * Doctrine event subscriber which encrypt/decrypt entities.
 */
interface DoctrineEncryptSubscriberInterface
{
    public const ENCRYPTED_SUFFIX = '<ENC>';

    public function __construct(
        LoggerInterface $logger,
        Reader $annReader,
        EncryptorInterface $encryptor,
        array $annotationArray,
        bool $isDisabled
    );

    /**
     * Encrypt the password before it is written to the database.
     *
     * Notice that we do not recalculate changes otherwise the value will be written
     * every time (Because it is going to differ from the un-encrypted value)
     */
    public function onFlush(OnFlushEventArgs $args): void;

    /**
     * After we have persisted the entities, we want to have the
     * decrypted information available once more.
     */
    public function postUpdate(LifecycleEventArgs $args): void;

    /**
     * Listen a postLoad lifecycle event. Checking and decrypt entities
     * which have @Encrypted annotations.
     */
    public function postLoad(LifecycleEventArgs $args): void;

    /**
     * Realization of EventSubscriber interface method.
     *
     * @return array Return all events which this subscriber is listening
     */
    public function getSubscribedEvents(): array;
}
