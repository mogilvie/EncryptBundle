<?php

namespace SpecShaper\EncryptBundle\Encryptors;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class EncryptorFactory
{
    public const SUPPORTED_EXTENSION_OPENSSL = OpenSslEncryptor::class;

    private EventDispatcherInterface $dispatcher;

    public function __construct(EventDispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * Create service will return the desired encryption service.
     *
     * @param string      $encryptKey     256-bit encryption key
     * @param string|null $encryptorClass the desired encryptor, defaults to OpenSSL, but can be overridden by passing a classname
     */
    public function createService(string $encryptKey, ?string $encryptorClass = self::SUPPORTED_EXTENSION_OPENSSL): EncryptorInterface
    {
        $encryptor = new $encryptorClass($this->dispatcher);
        $encryptor->setSecretKey($encryptKey);
        return $encryptor;
    }
}
