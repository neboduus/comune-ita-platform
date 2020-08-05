<?php

namespace AppBundle\FormIO;

use Symfony\Component\Form\FormFactory;
use Symfony\Component\Form\FormFactoryInterface;

class Validator
{
  /**
   * @var FormFactory
   */
  private $formFactory;

  private $schemaFactory;

  public function __construct(FormFactoryInterface $formFactory, SchemaFactoryInterface $schemaFactory)
  {
    $this->formFactory = $formFactory;
    $this->schemaFactory = $schemaFactory;
  }

  public function validateSchema(Schema $schema, array $constraint)
  {

  }

  /**
   * @param $formIOId
   * @param $data
   * @param array $validateFields
   * @return string[]
   */
  public function validateData($formIOId, $data, $validateFields = [])
  {
    $form = $this->formFactory->create(FormIOType::class, null, [
      'formio' => $formIOId,
      'formio_validate_fields' => $validateFields,
    ]);

    $schema = $this->schemaFactory->createFromFormId($formIOId);
    if (is_string($data)){
      $data = json_decode($data, true);
    }
    $data = $schema->getDataBuilder()->setDataFromArray($data)->toFullFilledFlatArray('form_name');
    if (!empty($validateFields)){
      $fieldsData = [];
      foreach ($validateFields as $field){
        $component = $schema->getComponent($field);
        $componentFromName = $component->getFormName();
        if (isset($data[$componentFromName])){
          $fieldsData[$componentFromName] = $data[$componentFromName];
        }
      }
      $form->submit($fieldsData);
    }else{
      $form->submit($data);
    }


    $errors = [];
    if ($form->isSubmitted() && !$form->isValid()) {
      foreach ($form->getErrors(true) as $formError) {
        $message = $formError->getOrigin()->getConfig()->getOption('label').': '.$formError->getMessage();
        if (isset($data[$formError->getOrigin()->getName()])) {
          $message .= ' ('.$data[$formError->getOrigin()->getName()].')';
        }
        $errors[$formError->getOrigin()->getName()] = $message;
      }
    }

    return $errors;
  }

  public function transform($value)
  {
    if (is_string($value)) {
      $value = json_decode($value, true);
    }
    if (!is_array($value)) {
      return null;
    }
    $data = $this->removeDataLevels($value);

    return $this->prefixKey('', $data);
  }

  private function removeDataLevels($original)
  {
    $data = new \ArrayObject();
    foreach ($original as $key => $value) {
      if (is_array($value)) {
        if (isset($value['data'])) {
          $data[$key] = $this->removeDataLevels($value['data']);
        }
      } else {
        $data[$key] = $value;
      }
    }

    return $data->getArrayCopy();
  }

  private function prefixKey($prefix, $array)
  {
    $result = array();
    foreach ($array as $key => $value) {
      if (is_array($value)) {
        $result = array_merge($result, $this->prefixKey($prefix.$key.':', $value));
      } else {
        $result[$prefix.$key] = $value;
      }
    }

    return $result;
  }
}
