<?php

namespace SpecShaper\EncryptBundle\Exception;

/**
 * Encrypt Exception.
 **
 * @author      Mark Ogilvie <mark.ogilvie@specshaper.com>
 */
class EncryptException extends \Exception
{
    /**
     * The value trying to be encrypted.
     */
    private string $value;

    /**
     * Constructor.
     *
     * Typically, provide a custom message key when throwing the exception.
     * Set the text and html of the exception in the messages' translation file.
     *
     * @param string|null $message Optional message
     * @param $value
     */
    public function __construct(?string $message = null, $value = null)
    {
        if (null === $message) {
            $message = 'sseb.exception.encryptionException';
        }

        if (null !== $value) {
            $this->value = $value;
        }

        parent::__construct($message);
    }

    public function getValue(): mixed
    {
        return $this->value;
    }

    public function setValue(mixed $value): EncryptException
    {
        $this->value = $value;

        return $this;
    }
}
