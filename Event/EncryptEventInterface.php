<?php

namespace SpecShaper\EncryptBundle\Event;

/**
 * EncryptEventInterface.
 */
interface EncryptEventInterface
{
    public function getValue(): ?string;

    public function setValue(?string $value): void;
}
