<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Class AtLeastOneAttachmentConstraint
 * @Annotation
 */
class AtLeastOneAttachmentConstraint extends Constraint
{
    public $message = 'The field must contain at least one attachment';
}
