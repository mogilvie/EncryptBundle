<?php

namespace SpecShaper\EncryptBundle\Encryptors;

/**
 * Encryptor interface for encryptors.
 */
interface EncryptorInterface
{
    public function setSecretKey(string $key): void;

    /**
     * Must accept data and return encrypted data.
     *
     * @param string|null $data Unencrypted string
     *
     * @return string|null Encrypted string
     */
    public function encrypt(?string $data, ?string $columnName = null): ?string;

    /**
     * Must accept data and return decrypted data.
     *
     * @param string $data Encrypted string
     *
     * @return string Unencrypted string
     */
    public function decrypt(?string $data, ?string $columnName = null): ?string;
}
