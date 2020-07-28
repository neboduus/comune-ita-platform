<?php


namespace AppBundle\Model;


class FormIOFlowStep extends FlowStep
{
  const TYPE = 'formio';

  public function __construct($formId, $formData)
  {
    $this->setIdentifier($formId)
      ->setType(self::TYPE)
      ->addParameter('formio_id', $formId)
      ->addParameter('formio_data', $formData);
  }
}
