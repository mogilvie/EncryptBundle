<?php

/**
 * AppBundle/Event/EmployeeNewEvent.php.
 *
 * LICENSE: Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential. SpecShaper is an SaaS product and no license is
 * granted to copy or distribute the source files.
 *
 * @author      Mark Ogilvie <mark.ogilvie@specshaper.com>
 * @copyright   (c) 2015, SpecShaper - All rights reserved
 * @license     http://URL name
 *
 * @version     Release: 1.0.0
 *
 * @since       Available since Release 1.0.0
 */

namespace SpecShaper\EncryptBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * EncryptEvent.
 *
 */
class EncryptEvent extends Event implements EncryptEventInterface
{

    /**
     * The string / object to be encrypted or decrypted.
     *
     * @var string
     */
    protected $value;

    /**
     * EncryptEvent constructor.
     *
     * @param $value
     */
    public function __construct($value)
    {
        $this->value= $value;
    }

    /**
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param string $value
     */
    public function setValue($value)
    {
        $this->value = $value;
    }

}
