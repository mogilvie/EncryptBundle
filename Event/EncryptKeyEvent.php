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
class EncryptKeyEvent extends Event
{

    /**
     * The key to be used instead of the parameter key,
     *
     * @var string|null
     */
    protected $key;

    /**
     * @return string|null
     */
    public function getKey(): ?string
    {
        return $this->key;
    }

    /**
     * @param string $key
     * @return EncryptKeyEvent
     */
    public function setKey(string $key): EncryptKeyEvent
    {
        $this->key = $key;
        return $this;
    }
    
}
