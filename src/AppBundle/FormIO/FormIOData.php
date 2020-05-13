<?php

namespace AppBundle\FormIO;


class FormIOData
{
  /**
   * @var FormIOData[]
   */
  private $data = [];

  private $name;

  private $value;

  public function __construct($name = null)
  {
    $this->name = $name;
  }

  public function hasData()
  {
    return count($this->data) > 0;
  }

  public function setData($name)
  {
    if (!isset($this->data[$name])){
      $this->data[$name] = new FormIOData($name);
    }
    return $this->data[$name];
  }

  /**
   * @param $value
   * @return $this
   */
  public function setValue($value)
  {
    $this->value = $value;

    return $this;
  }

  /**
   * @return string
   */
  public function getName()
  {
    return $this->name;
  }

  /**
   * @return mixed
   */
  public function getValue()
  {
    return $this->value;
  }

  public function toArray()
  {
    if (!empty($this->data)){
      $data = [];
      foreach ($this->data as $item){
        $data['data'][$item->getName()] = $item->toArray();
      }
    }else{
      $data = $this->value;
    }

    return $data;
  }

  public function toFlatArray(\ArrayObject $flatten, $separator = '.', $parentKey = '')
  {
    if (!empty($parentKey)){
      $parentKey .= $separator;
    }
    foreach ($this->data as $item){
      if (!$item->hasData()){
        $flatten[$parentKey.$item->getName()] = $item->getValue();
      }else{
        $item->toFlatArray($flatten, $separator, $parentKey.$item->getName());
      }
    }
  }

}
