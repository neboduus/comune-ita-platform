<?php

namespace AppBundle\Payment;

use AppBundle\Entity\Pratica;
use AppBundle\Form\Admin\Servizio\PaymentDataType;

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

  public function toJson()
  {
    return json_encode($this->toArray());
  }

  /**
   * @param Pratica $pratica
   * @return array|false
   */
  public static function getSanitizedPaymentData(Pratica $pratica)
  {

    $data = $pratica->getDematerializedForms();

    if (isset($data['flattened'][PaymentDataType::PAYMENT_AMOUNT]) && is_numeric(str_replace(',', '.', $data['flattened'][PaymentDataType::PAYMENT_AMOUNT]))) {
      $paymentData[PaymentDataType::PAYMENT_AMOUNT] = str_replace(',', '.', $data['flattened'][PaymentDataType::PAYMENT_AMOUNT]);

      if (isset($data['flattened'][PaymentDataType::PAYMENT_FINANCIAL_REPORT])) {
        $paymentData[PaymentDataType::PAYMENT_FINANCIAL_REPORT] = $data['flattened'][PaymentDataType::PAYMENT_FINANCIAL_REPORT];
      }
      return $paymentData;
    }

    // Fallback su configurazione dal backend
    if (isset($pratica->getServizio()->getPaymentParameters()['total_amounts'])
      && is_numeric(str_replace(',', '.', $pratica->getServizio()->getPaymentParameters()['total_amounts']))) {
      $paymentData[PaymentDataType::PAYMENT_AMOUNT] = str_replace(',', '.', $pratica->getServizio()->getPaymentParameters()['total_amounts']);
      return $paymentData;
    }
    return false;

  }

}
