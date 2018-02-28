<?php
/**
 * Created by PhpStorm.
 * User: mark
 * Date: 22/10/17
 * Time: 22:43
 */

namespace SpecShaper\EncryptBundle\Encryptors;


class EncryptorFactory
{
    const SUPPORTED_EXTENSIONS = [
        OpenSslEncryptor::class
    ];

    /**
     * @param $method
     * @param $encryptKey
     * @return OpenSslEncryptor
     */
    public function createService($method, $encryptKey)
    {

        switch($method){
            default:
                $encryptor = new OpenSslEncryptor($encryptKey);
        }

        return $encryptor;
    }

}