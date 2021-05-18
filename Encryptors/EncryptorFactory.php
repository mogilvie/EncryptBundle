<?php
/**
 * Created by PhpStorm.
 * User: mark
 * Date: 22/10/17
 * Time: 22:43
 */
namespace SpecShaper\EncryptBundle\Encryptors;

use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class EncryptorFactory
{
    const SUPPORTED_EXTENSIONS = [
        OpenSslEncryptor::class
    ];

    private $logger;
    
    private $dispatcher;

    public function __construct(LoggerInterface $logger, EventDispatcherInterface $dispatcher)
    {
        $this->logger = $logger;
        $this->dispatcher = $dispatcher;
    }

    /**
     * @param $method
     * @param $encryptKey
     * @return OpenSslEncryptor
     */
    public function createService($encryptor, $encryptKey)
    {
        switch($encryptor){
            default:
                $encryptor = new OpenSslEncryptor($this->dispatcher, $encryptKey);
        }

        return $encryptor;
    }

}
