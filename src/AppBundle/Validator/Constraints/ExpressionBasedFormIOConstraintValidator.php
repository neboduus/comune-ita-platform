<?php

namespace AppBundle\Validator\Constraints;

use AppBundle\FormIO\ExpressionValidator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class ExpressionBasedFormIOConstraintValidator extends ConstraintValidator
{
  private $validator;

  public function __construct(ExpressionValidator $validator)
  {
    $this->validator = $validator;
  }

  public function validate($value, Constraint $constraint)
  {
    if (!$constraint instanceof ExpressionBasedFormIOConstraint) {
      throw new UnexpectedTypeException($constraint, ExpressionBasedFormIOConstraint::class);
    }

    $errors = $this->validator->validateData(
      $constraint->service,
      $value
    );

    if (!empty($errors)) {
      foreach ($errors as $error) {
        $this->context->buildViolation($error)->addViolation();
      }
    }
  }
}
