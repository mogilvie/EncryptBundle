<?php

namespace SpecShaper\EncryptBundle\EventListener;

use Doctrine\Persistence\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\EntityManagerInterface;
use SpecShaper\EncryptBundle\Encryptors\EncryptorInterface;

/**
 * Doctrine event listener interface which encrypts/decrypts entities.
 */
interface DoctrineEncryptListenerInterface
{
    public const ENCRYPTED_SUFFIX = '<ENC>';

    public function __construct(
        EncryptorInterface $encryptor,
        EntityManagerInterface $em,
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
}
