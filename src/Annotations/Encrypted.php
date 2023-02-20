<?php

namespace SpecShaper\EncryptBundle\Annotations;

use Attribute;
use Doctrine\Common\Annotations\Annotation;

/**
 * @Annotation
 * @Target("ALL")
 */
#[\Attribute(Attribute::TARGET_PROPERTY)]
final class Encrypted
{
}

