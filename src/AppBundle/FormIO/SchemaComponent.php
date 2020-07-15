<?php

namespace AppBundle\FormIO;

use ArrayAccess;
use JsonSerializable;
use RuntimeException;

class SchemaComponent implements ArrayAccess, JsonSerializable
{
  private $name;

  private $formType;

  private $formOptions;

  private $keys = [
    'name',
    'form_name',
    'form_type',
    'form_options',
    'label',
    'type',
    'is_required',
  ];

  /**
   * @param string $name
   * @param string $formType fqcn form type
   * @param array $formOptions form type options
   */
  public function __construct($name, $formType, $formOptions)
  {
    $this->name = $name;
    $this->formType = $formType;
    $this->formOptions = $formOptions;
  }

  // needed by array_column
  public function __isset($offset)
  {
    return $this->offsetExists($offset);
  }

  public function offsetExists($offset)
  {
    return in_array($offset, $this->keys);
  }

  public function offsetSet($offset, $value)
  {
    throw new RuntimeException("Can not modify ".static::class);
  }

  public function offsetUnset($offset)
  {
    throw new RuntimeException("Can not modify ".static::class);
  }

  public function jsonSerialize()
  {
    $data = $this->toArray();
    //remove form info
    unset($data['form_name']);
    unset($data['form_type']);
    unset($data['form_options']);

    return $data;
  }

  public function toArray()
  {
    $data = [];
    foreach ($this->keys as $key) {
      $data[$key] = $this->offsetGet($key);
    }

    return $data;
  }

  // needed by array_column
  public function __get($offset)
  {
    return $this->offsetGet($offset);
  }

  public function offsetGet($offset)
  {
    switch ($offset) {
      case 'name':
        return $this->getName();
        break;

      case 'form_name':
        return $this->getFormName();
        break;

      case 'form_type':
        return $this->getFormType();
        break;

      case 'form_options':
        return $this->getFormOptions();
        break;

      case 'label':
        return $this->getLabel();
        break;

      case 'type':
        return $this->getType();

      case 'is_required':
        return $this->isRequired();
        break;
    }

    return null;
  }

  /**
   * @return string
   */
  public function getName()
  {
    return $this->name;
  }

  /**
   * @return string
   */
  public function getFormName()
  {
    return implode(':', explode('.', $this->name));
  }

  /**
   * @return string
   */
  public function getFormType()
  {
    return $this->formType;
  }

  /**
   * @return array
   */
  public function getFormOptions()
  {
    return $this->formOptions;
  }

  /**
   * @return string|null
   */
  public function getLabel()
  {
    return isset($this->formOptions['label']) ? strip_tags($this->formOptions['label']) : null;
  }

  /**
   * @return string
   */
  public function getType()
  {
    return str_replace('type', '', strtolower(substr(strrchr($this->formType, '\\'), 1)));
  }

  /**
   * @return bool
   */
  public function isRequired()
  {
    return isset($this->formOptions['required']) && $this->formOptions['required'];
  }

  public function __toString()
  {
    return $this->getName();
  }
}
