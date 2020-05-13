<?php

namespace AppBundle\Validator\Constraints;

use AppBundle\FormIO\Validator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class ServerSideFormIOConstraintValidator extends ConstraintValidator
{
  private $formIOValidator;

  public function __construct(Validator $formIOValidator)
  {
    $this->formIOValidator = $formIOValidator;
  }

  public function validate($value, Constraint $constraint)
  {
    if (!$constraint instanceof ServerSideFormIOConstraint) {
      throw new UnexpectedTypeException($constraint, ServerSideFormIOConstraint::class);
    }

    if (null === $value || '' === $value) {
      return;
    }

    $errors = $this->formIOValidator->validateData($constraint->formIOId, $value, $constraint->validateFields);
    foreach ($errors as $error) {
      $this->context->buildViolation($error)->addViolation();
    }
  }

}
