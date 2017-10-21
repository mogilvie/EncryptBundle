<?php

namespace SpecShaper\EncryptBundle\Encryptors;

/**
 * Class for OpenSSL encryption
 *
 * @author Mark Ogilvie <mark.ogilvie@ogilvieconsulting.net>
 */
class OpenSslEncryptor implements EncryptorInterface
{
    const METHOD = 'aes-256-cbc';

    /**
     * Secret key for aes algorythm
     * @var string
     */
    private $secretKey;

    /**
     * Initialization of encryptor
     * @param string $key
     */
    public function __construct($key)
    {
        $this->secretKey = $key;
    }

    /**
     * @param string $data
     * @return string
     * @throws \Exception
     */
    public function encrypt($data)
    {
        if (is_null($data)) {
            return $data;
        }

        $key = $this->secretKey;

        if (mb_strlen($key, '8bit') !== 32) {
            throw new \Exception("Needs a 256-bit key!");
        }
        $ivsize = openssl_cipher_iv_length(self::METHOD);
        $iv = openssl_random_pseudo_bytes($ivsize);

        $ciphertext = openssl_encrypt(
            $data,

            self::METHOD,
            $key,
            OPENSSL_RAW_DATA,
            $iv
        );

        $encoded = base64_encode($iv . $ciphertext);

        return $encoded;
    }

    /**
     * @param string $data
     * @return string
     * @throws \Exception
     */
    public function decrypt($data)
    {
        if (is_null($data)) {
            return $data;
        }

        $data = base64_decode($data);

        $key = $this->secretKey;

        if (mb_strlen($key, '8bit') !== 32) {
            throw new \Exception("Needs a 256-bit key!");
        }
        $ivsize = openssl_cipher_iv_length(self::METHOD);
        $iv = mb_substr($data, 0, $ivsize, '8bit');
        $ciphertext = mb_substr($data, $ivsize, null, '8bit');

        return openssl_decrypt(
            $ciphertext,
            self::METHOD,
            $key,
            OPENSSL_RAW_DATA,
            $iv
        );
    }
}
