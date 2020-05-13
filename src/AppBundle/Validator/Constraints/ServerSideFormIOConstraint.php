<?php

namespace AppBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class ServerSideFormIOConstraint extends Constraint
{
  public static $flow_groups = [
    'flow_formIO_step1',
    'flow_FormIOAnonymous_step1',
  ];

  public $formIOId;

  public $validateFields = [];

  public function getRequiredOptions()
  {
    return ['formIOId'];
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
    return 'formio.constraint_validator';
  }
}
