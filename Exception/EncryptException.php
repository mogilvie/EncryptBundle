<?php

/**
 * AppBundle\Exception\InvitationException.php.
 *
 * LICENSE: Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential. SpecShaper is an SaaS product and no license is
 * granted to copy or distribute the source files.
 *
 * @author     Written by Mark Ogilvie <mark.ogilvie@specshaper.com>, 11 2015
 * @copyright  (c) 2015, SpecShaper - All rights reserved
 * @license    http://URL name
 *
 * @version     Release: 1.0.0
 *
 * @since       Available since Release 1.0.0
 */
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
    private $value;

    /**
     * Constructor.
     *
     * Typically provide a custom message key when throwing the exception.
     * Set the text and html of the exception in the messages translation file.
     *
     * @since Available since Release 1.0.0
     *
     * @param string $message Optional message
     */
    public function __construct($message = null, $value = null)
    {
        if ($message === null) {
            $message = 'sseb.exception.encryptionException';
        }

        if ($value !== null) {
            $this->value = $value;
        }


        parent::__construct($message);
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Set Value.
     *
     * @param mixed $value
     *
     * @return EncryptException
     */
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }


}
