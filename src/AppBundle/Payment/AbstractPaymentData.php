<?php

namespace AppBundle\Payment;

abstract class AbstractPaymentData implements PaymentDataInterface
{
  protected $attributes = array();

  /**
   * AbstractPaymentData constructor.
   * @param array $array
   */
  public function __construct(array $array = array())
  {
    foreach ($array as $key => $value) {
      if ($this->hasFieldByName($key)) {
        $this->attributes[$key] = $value;
      }
    }
  }

  /**
   * @param string $field
   * @return array|mixed|null
   */
  public function getFieldValue(string $field)
  {
    if (isset($this->attributes[$field])) {
      return $this->attributes[$field];
    }
    return null;
  }

  /**
   * @param $name
   * @return bool
   */
  protected function hasFieldByName($name)
  {
    foreach (static::getFields() as $field) {
      if ($field == $name) {
        return true;
      }
    }
    return false;
  }

  /**
   * @param $name
   * @return bool
   */
  protected function getFieldByName($name)
  {
    foreach (static::getFields() as $field) {
      if ($field == $name) {
        return $field;
      }
    }
    return false;
  }

  /**
   * @param $data
   * @return PaymentDataInterface
   */
  public static function fromData($data): PaymentDataInterface
  {
    if (is_array($data)) {
      return self::fromArray($data);
    } else {
      return self::fromJson($data);
    }
  }

  /**
   * @param $data
   * @return mixed|void
   */
  public static function getSimplifiedData($data)
  {
    return $data;
  }

  /**
   * @param $array
   * @return static
   */
  private static function fromArray($array)
  {
    return new static($array);
  }

  public function toArray()
  {
    return $this->attributes;
  }

  private static function fromJson($json)
  {
    return new static(json_decode($json, true));
  }

  function toJson()
  {
    return json_encode($this->toArray());
  }


}