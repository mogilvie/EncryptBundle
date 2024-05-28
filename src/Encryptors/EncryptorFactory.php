<?php

namespace SpecShaper\EncryptBundle\Encryptors;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class EncryptorFactory
{
    public const SUPPORTED_EXTENSION_OPENSSL = AesCbcEncryptor::class;

    private EventDispatcherInterface $dispatcher;

    public function __construct(EventDispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * Create service will return the desired encryption service.
     *
     * @param string      $encryptKey     256-bit encryption key
     * @param string      $defaultAssociatedData     A fallback string used for AES-GBC-256 encryption.
     * @param string|null $encryptorClass the desired encryptor, defaults to OpenSSL, but can be overridden by passing a classname
     */
    public function createService(string $encryptKey, ?string $defaultAssociatedData = null, ?string $encryptorClass = self::SUPPORTED_EXTENSION_OPENSSL): EncryptorInterface
    {
        $encryptor = new $encryptorClass($this->dispatcher);
        $encryptor->setSecretKey($encryptKey);

        if(method_exists($encryptorClass, 'setDefaultAssociatedData')){
            $encryptor->setDefaultAssociatedData($defaultAssociatedData);
        }

        return $encryptor;
    }
}
