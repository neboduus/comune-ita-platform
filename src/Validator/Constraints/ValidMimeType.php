<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Class ValidMimeTypeConstraint
 * @Annotation
 */
class ValidMimeType extends Constraint
{

    const TRANSLATION_ID = 'allegato.non_supportato';

    /**
     * @return string
     */
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
