<?php

namespace SpecShaper\EncryptBundle\Event;

interface EncryptEventInterface
{
    public function getValue(): ?string;

    public function setValue(?string $value): void;
}
