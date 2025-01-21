<?php

namespace SpecShaper\EncryptBundle\Encryptors;

use SpecShaper\EncryptBundle\Event\EncryptKeyEvent;
use SpecShaper\EncryptBundle\Event\EncryptKeyEvents;
use SpecShaper\EncryptBundle\Exception\EncryptException;
use SpecShaper\EncryptBundle\EventListener\DoctrineEncryptListenerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Service\Attribute\Required;

class AesGcmEncryptor implements EncryptorInterface
{
    public const METHOD = 'aes-256-gcm';

    /**
     * Secret key stored in the .env file and passed via parameters in the Encryptor Factory.
     */
    private string $secretKey;

    private EventDispatcherInterface $dispatcher;

    private ?string $defaultAssociatedData;

    /**
     * OpenSslEncryptor constructor.
     */
    public function __construct(EventDispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    public function __toString(): string
    {
        return self::class.':'.self::METHOD;
    }

    public function setSecretKey(string $secretKey): void
    {
        $this->secretKey = $secretKey;
    }

    public function setDefaultAssociatedData(?string $defaultAssociatedData): void
    {
        $this->defaultAssociatedData = $defaultAssociatedData;
    }

    /**
     * @throws \Exception
     */
    public function encrypt(?string $data, ?string $columnName): ?string
    {
        if (is_null($data)) {
            return null;
        }

        if (DoctrineEncryptListenerInterface::ENCRYPTED_SUFFIX === substr($data, -5)) {
            return $data;
        }

        $key = $this->getSecretKey();
        $ivsize = openssl_cipher_iv_length(self::METHOD);
        $iv = openssl_random_pseudo_bytes($ivsize);
        $tag = '';
        $associatedData = $columnName ?? $this->defaultAssociatedData;

        $ciphertext = openssl_encrypt(
            $data,
            self::METHOD,
            $key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            $associatedData
        );

        if ($ciphertext === false) {
            throw new EncryptException('Encryption failed.');
        }

        return base64_encode($iv.$tag.$ciphertext).DoctrineEncryptListenerInterface::ENCRYPTED_SUFFIX;
    }

    /**
     * @throws \Exception
     */
    public function decrypt(?string $data, ?string $columnName): ?string
    {
        if (is_null($data)) {
            return null;
        }

        if (DoctrineEncryptListenerInterface::ENCRYPTED_SUFFIX !== substr($data, -5)) {
            return $data;
        }

        $data = substr($data, 0, -5);
        if (empty($data)) {
            return $data;
        }

        $key = $this->getSecretKey();
        $data = base64_decode($data);
        $ivsize = openssl_cipher_iv_length(self::METHOD);
        $iv = mb_substr($data, 0, $ivsize, '8bit');
        $tag = mb_substr($data, $ivsize, 16, '8bit');
        $ciphertext = mb_substr($data, $ivsize + 16, null, '8bit');

        $plaintext = openssl_decrypt(
            $ciphertext,
            self::METHOD,
            $key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            $columnName
        );

        if ($plaintext === false) {
            throw new EncryptException('Decryption failed.');
        }

        return $plaintext;
    }


    /**
     * Get the secret key.
     *
     * Decode the parameters file base64 key.
     * Check that the key is 256 bit.
     *
     * @throws \Exception
     */
    private function getSecretKey(): string
    {
        $getKeyEvent = new EncryptKeyEvent();
        $this->dispatcher->dispatch($getKeyEvent, EncryptKeyEvents::LOAD_KEY);

        if (null !== $getKeyEvent->getKey()) {
            $this->secretKey = $getKeyEvent->getKey();
        }

        if (empty($this->secretKey)) {
            throw new EncryptException('The bundle specshaper\encrypt-bundle requires a parameter.yml value for "encrypt_key"
            Use cli command "php bin/console encrypt:genkey" to create a key, or set via a listener on the EncryptKeyEvents::LOAD_KEY event');
        }

        $key = base64_decode($this->secretKey);
        $keyLengthOctet = mb_strlen($key, '8bit');

        if (32 !== $keyLengthOctet) {
            throw new \Exception("Needs a 256-bit key, '".($keyLengthOctet * 8)."'bit given!");
        }

        return $key;
    }
}