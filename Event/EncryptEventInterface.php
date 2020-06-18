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

/**
 * EncryptEventInterface
 *
 */
interface EncryptEventInterface
{
    /**
     * @return string
     */
    public function getValue();

    /**
     * @param string $value
     */
    public function setValue($value);

}
