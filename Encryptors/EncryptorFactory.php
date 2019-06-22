<?php
/**
 * Created by PhpStorm.
 * User: mark
 * Date: 22/10/17
 * Time: 22:43
 */

namespace SpecShaper\EncryptBundle\Encryptors;

use Psr\Log\LoggerInterface;

class EncryptorFactory
{
    const SUPPORTED_EXTENSIONS = [
        OpenSslEncryptor::class
    ];

    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param $method
     * @param $encryptKey
     * @return OpenSslEncryptor
     */
    public function createService($encryptor, $encryptKey)
    {

        // Log an error if there is no value set for the encrypt_key.
        if($encryptKey === null){
            $this->logger->error('The bundle specshaper\encrypt-bundle requires a parameter.yml value for "encrypt_key". 
            Use cli command "php bin/console encrypt:genkey" to create a key.');
        }

        switch($encryptor){
            default:
                $encryptor = new OpenSslEncryptor($encryptKey);
        }

        return $encryptor;
    }

}