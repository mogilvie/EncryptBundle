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
interface DoctrineEncryptSubscriberInterface
{

    public function __construct(Reader $annReader, EncryptorInterface $encryptor, $annotationArray, $isDisabled);

    /**
     * Encrypt the password before it is written to the database.
     *
     * Notice that we do not recalculate changes otherwise the password will be written
     * every time (Because it is going to differ from the un-encrypted value)
     *
     * @param OnFlushEventArgs $args
     */
    public function onFlush(OnFlushEventArgs $args);



    /**
     * After we have persisted the entities, we want to have the
     * decrypted information available once more.
     * @param PostFlushEventArgs $args
     */
    public function postFlush(PostFlushEventArgs $args);

    /**
     * Listen a postLoad lifecycle event. Checking and decrypt entities
     * which have @Encrypted annotations
     * @param LifecycleEventArgs $args
     */
    public function postLoad(LifecycleEventArgs $args);

    /**
     * Realization of EventSubscriber interface method.
     * @return array Return all events which this subscriber is listening
     */
    public function getSubscribedEvents();

    /**
     * Capitalize string
     * @param string $word
     * @return string
     */
    public static function capitalize($word);

    public function encrypt(EncryptEvent $event);
    public function decrypt(EncryptEvent $event);


}
