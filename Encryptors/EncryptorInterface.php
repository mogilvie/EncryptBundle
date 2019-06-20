<?php

namespace SpecShaper\EncryptBundle\Encryptors;

/**
 * Encryptor interface for encryptors
 *
 * @author Victor Melnik <melnikvictorl@gmail.com>
 */
interface EncryptorInterface
{

    /**
     * Must accept data and return encrypted data
     * @param string $data
     * @return string
     */
    public function encrypt($data);

    /**
     * Must accept data and return decrypted data
     * @param string $data
     * @return string
     */
    public function decrypt($data);
}
