<?php

namespace SpecShaper\EncryptBundle\Subscribers;

use SpecShaper\EncryptBundle\Encryptors\EncryptorInterface;
use SpecShaper\EncryptBundle\Event\EncryptEventInterface;
use SpecShaper\EncryptBundle\Event\EncryptEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Doctrine event subscriber which encrypt/decrypt entities.
 */
class EncryptEventSubscriber implements EventSubscriberInterface
{
    /**
     * Encryptor created by the factory service.
     */
    protected EncryptorInterface $encryptor;

    /**
     * Store if the encryption is enabled or disabled in config.
     */
    private bool $isDisabled;

    /**
     * EncryptSubscriber constructor.
     *
     * @param $isDisabled
     */
    public function __construct(EncryptorInterface $encryptor, bool $isDisabled)
    {
        $this->encryptor = $encryptor;
        $this->isDisabled = $isDisabled;
    }

    /**
     * Return the encryptor.
     */
    public function getEncryptor(): EncryptorInterface
    {
        return $this->encryptor;
    }

    /**
     * Realization of EventSubscriber interface method.
     *
     * @return array Return all events which this subscriber is listening
     */
    public static function getSubscribedEvents(): array
    {
        return [
            EncryptEvents::ENCRYPT => 'encrypt',
            EncryptEvents::DECRYPT => 'decrypt',
        ];
    }

    /**
     * Use an Encrypt even to encrypt a value.
     */
    public function encrypt(EncryptEventInterface $event): EncryptEventInterface
    {
        $value = $event->getValue();

        if (false === $this->isDisabled) {
            $value = $this->encryptor->encrypt($value);
        }

        $event->setValue($value);

        return $event;
    }

    /**
     * Use a decrypt event to decrypt a single value.
     */
    public function decrypt(EncryptEventInterface $event): EncryptEventInterface
    {
        $value = $event->getValue();

        $decrypted = $this->getEncryptor()->decrypt($value);

        $event->setValue($decrypted);

        return $event;
    }

    /**
     * Decrypt a value.
     *
     * If the value is an object, or if it does not contain the suffic <ENC> then return the value iteslf back.
     * Otherwise, decrypt the value and return.
     */
    public function decryptValue(?string $value): ?string
    {
        return $this->getEncryptor()->decrypt($value);
    }
}
