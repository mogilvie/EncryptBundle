<?php

namespace SpecShaper\EncryptBundle\Twig;

use SpecShaper\EncryptBundle\Encryptors\EncryptorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;


class EncryptExtension extends AbstractExtension
{
    /**
     * @var EncryptorInterface
     */
    private $encryptor;

    public function __construct(EncryptorInterface $encryptor)
    {
        $this->encryptor = $encryptor;
    }

    /**
     * @return TwigFilter[]
     */
    public function getFilters(): array
    {
        return array(
            new TwigFilter('decrypt', array($this, 'decryptFilter'))
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
