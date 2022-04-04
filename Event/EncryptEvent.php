<?php

namespace SpecShaper\EncryptBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

class EncryptEvent extends Event implements EncryptEventInterface
{
    /**
     * The string / object to be encrypted or decrypted.
     */
    protected string $value;

    /**
     * EncryptEvent constructor.
     *
     * @param string $value
     */
    public function __construct(?string $value)
    {
        $this->value = $value;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function setValue(?string $value): void
    {
        $this->value = $value;
    }
}
