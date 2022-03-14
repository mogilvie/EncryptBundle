<?php
/**
 * Created by PhpStorm.
 * User: Mark Ogilvie
 * Date: 21/10/17
 * Time: 18:22
 */

namespace SpecShaper\EncryptBundle\Annotations;

use Attribute;
use Doctrine\Common\Annotations\Annotation;

/**
 * @Annotation
 * @Target("ALL")
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Encrypted extends Annotation
{

}

