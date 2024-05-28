<?php

namespace SpecShaper\EncryptBundle\Encryptors;

use Exception;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
class AESGCMEncryptor
{
    private $secretKey;

    public function __construct(ParameterBagInterface $params)
    {
        $this->secretKey = $params->get('encrypt_secret');
    }

    public function encrypt(string $data, string $columnName): string
    {
        $iv = random_bytes(12); // 96-bit IV for AES-GCM
        $tag = null;
        $cipherText = openssl_encrypt(
            $data,
            'aes-256-gcm',
            $this->secretKey,
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            $columnName // Using the column name as associated data
        );

        if ($cipherText === false) {
            throw new Exception('Encryption failed: ' . openssl_error_string());
        }

        return base64_encode($iv . $tag . $cipherText);
    }

    public function decrypt(string $encryptedData, string $columnName): string
    {
        $data = base64_decode($encryptedData);
        $iv = substr($data, 0, 12);
        $tag = substr($data, 12, 16);
        $cipherText = substr($data, 28);

        $plainText = openssl_decrypt(
            $cipherText,
            'aes-256-gcm',
            $this->secretKey,
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            $columnName // Using the column name as associated data
        );

        if ($plainText === false) {
            throw new Exception('Decryption failed: ' . openssl_error_string());
        }

        return $plainText;
    }
}