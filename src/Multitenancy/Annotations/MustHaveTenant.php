<?php

namespace App\Multitenancy\Annotations;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ConfigurationInterface;

/**
 * @Annotation
 * @Target({"CLASS","METHOD"})
 */
class MustHaveTenant implements ConfigurationInterface
{
    public function getAliasName()
    {
        return 'must_have_tenant';
    }

    public function allowArray()
    {
        return false;
    }
}
