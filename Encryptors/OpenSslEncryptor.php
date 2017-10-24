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
     * base64 key as stored in the parameters.yml file.
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
        // If not data return data (null)
        if (is_null($data)) {
            return $data;
        }

        $key = $this->getSecretKey();

        // Create a cipher of the appropriate length for this method.
        $ivsize = openssl_cipher_iv_length(self::METHOD);
        $iv = openssl_random_pseudo_bytes($ivsize);

        // Create the ecnryption.
        $ciphertext = openssl_encrypt(
            $data,
            self::METHOD,
            $key,
            OPENSSL_RAW_DATA,
            $iv
        );

        // Prefix the encoded tex with the iv and encode it to base 64.
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

        // If not data return data (null)
        if (is_null($data)) {
            return $data;
        }

        $key = $this->getSecretKey();

        $data = base64_decode($data);

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

    /**
     * Get the secret key.
     *
     * Decode the parameters file base64 key.
     * Check that the key is 256 bit.
     *
     * @return string
     * @throws \Exception
     */
    private function getSecretKey(){

        // Decode the key
        $key = base64_decode($this->secretKey);

        $keyLengthOctet = mb_strlen($key, '8bit');

        if ($keyLengthOctet !== 32) {
            throw new \Exception("Needs a 256-bit key, '".($keyLengthOctet * 8)."'bit given!");
        }

        return $key;
    }
}
