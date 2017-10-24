<?php

namespace SpecShaper\EncryptBundle\Twig;

use SpecShaper\EncryptBundle\Encryptors\EncryptorInterface;


class EncryptExtension extends \Twig_Extension
{
    /**
     * @var EncryptorInterface
     */
    private $encryptor;

    public function __construct(EncryptorInterface $encryptor)
    {
        $this->encryptor = $encryptor;
    }

    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('decrypt', array($this, 'decryptFilter'))
        );
    }

    public function decryptFilter($data)
    {
        return  $this->encryptor->decrypt($data);
    }

    public function getName()
    {
        return 'spec_shaper_encrypt_extension';
    }
}
