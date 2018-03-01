<?php

namespace SpecShaper\EncryptBundle\Subscribers;


use SpecShaper\EncryptBundle\Encryptors\EncryptorInterface;
use SpecShaper\EncryptBundle\Event\EncryptEventInterface;
use SpecShaper\EncryptBundle\Event\EncryptEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Doctrine event subscriber which encrypt/decrypt entities
 */
class EncryptEventSubscriber implements EventSubscriberInterface
{
    /**
     * Encryptor created by the factory service.
     *
     * @var EncryptorInterface
     */
    protected $encryptor;


    /**
     * Store if the encryption is enabled or disabled in config.
     *
     * @var boolean
     */
    private $isDisabled;

    /**
     * EncryptSubscriber constructor.
     *
     * @param EncryptorInterface $encryptor
     * @param                    $isDisabled
     */
    public function __construct(EncryptorInterface $encryptor, $isDisabled)
    {
        $this->encryptor = $encryptor;
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
     * Realization of EventSubscriber interface method.
     * @return array Return all events which this subscriber is listening
     */
    public static function getSubscribedEvents()
    {
        return array(

            EncryptEvents::ENCRYPT => 'encrypt',
            EncryptEvents::DECRYPT => 'decrypt',
        );
    }

    /**
     * Use an Encrypt even to encrypt a value.
     *
     * @param EncryptEventInterface $event
     *
     * @return EncryptEventInterface
     */
    public function encrypt(EncryptEventInterface $event){

        $value = $event->getValue();

        if($this->isDisabled === false) {
            $value = $this->encryptor->encrypt($value);
        }

        $event->setValue($value);

        return $event;
    }

    /**
     * Use a decrypt event to decrypt a single value.
     *
     * @param EncryptEventInterface $event
     *
     * @return EncryptEventInterface
     */
    public function decrypt(EncryptEventInterface $event){

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
     *
     * @param $value
     *
     * @return string
     */
    public function decryptValue($value){

        // Else decrypt value and return.
        $decrypted = $this->getEncryptor()->decrypt($value);

        return $decrypted;

    }

}
