<?php

namespace AppBundle\Validator\Constraints;

use AppBundle\Entity\Servizio;
use Symfony\Component\Validator\Constraint;

class ExpressionBasedFormIOConstraint extends Constraint
{
  public static $flow_groups = [
    'flow_formIO_step1',
    'flow_FormIOAnonymous_step1',
  ];

  /**
   * @var Servizio
   */
  public $service;

  public function getRequiredOptions()
  {
    return ['service'];
  }

  public function __get($option)
  {
    if ('groups' === $option) {
      $this->groups = array_merge([self::DEFAULT_GROUP], self::$flow_groups);

      return $this->groups;
    }

    throw parent::__get($option);
  }

  public function validatedBy()
  {
    return 'formio.expression_based_validator';
  }
}
