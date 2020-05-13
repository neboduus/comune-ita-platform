<?php

namespace AppBundle\FormIO;

class DataBuilder
{
  private $schema;

  private $data;

  public function __construct(Schema $schema)
  {
    $this->schema = $schema;
    $this->data = new FormIOData();
  }

  public function setDataFromArray(array $source, FormIOData $data = null)
  {
    if (!$data){
      $data = $this->data;
    }
    foreach ($source as $name => $value){
      $item = $data->setData($name);
      if (is_array($value) && isset($value['data'])){
        $this->setDataFromArray($value['data'], $item);
      }else{
        $item->setValue($value);
      }
    }

    return $this;
  }

  public function set($name, $value)
  {
    if (!$this->schema->hasComponent($name)){
      throw new \InvalidArgumentException("Field $name not found in schema " . $this->schema->getId());
    }
    $nameParts = explode('.', $name);
    $data = $this->data;
    foreach ($nameParts as $namePart){
      $data = $data->setData($namePart);
    }
    $data->setValue($value);

    return $this;
  }

  public function toArray()
  {
    $data = $this->data->toArray();

    return $data['data'];
  }

  public function toFullFilledFlatArray($fieldColumn = 'name', $returnExtraAndMissing = false)
  {
    $flatten = new \ArrayObject();
    $this->data->toFlatArray($flatten, $fieldColumn == 'form_name' ? ':' : '.');

    $normalizedFlattenArray = [];
    $missing = [];
    $flattenArray = $flatten->getArrayCopy();

    $emptyData = array_fill_keys($this->schema->getComponentsColumns($fieldColumn), '');

    foreach ($emptyData as $key => $value){
      if (isset($flattenArray[$key])){
        $normalizedFlattenArray[$key] = $flattenArray[$key];
        unset($flattenArray[$key]);
      }else{
        $normalizedFlattenArray[$key] = '';
        $missing[] = $key;
      }
    }

    if ($returnExtraAndMissing) {
      if (count($flattenArray) > 0) {
        $normalizedFlattenArray['_extra'] = $flattenArray;
      }
      if (count($missing) > 0) {
        $normalizedFlattenArray['_missing'] = $missing;
      }
    }

    return $normalizedFlattenArray;
  }

  /**
   * @return FormIOData
   */
  public function getData(): FormIOData
  {
    return $this->data;
  }

}
