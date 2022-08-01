<?php

namespace App\Payment;


interface PaymentDataInterface
{

  const STATUS_PAYMENT_PENDING = 'pending';
  const STATUS_PAYMENT_PROCESSING = 'processing';
  const STATUS_PAYMENT_PAID = 'paid';
  const STATUS_PAYMENT_FAILED = 'failed';

  public function getIdentifier(): string;

  /**
   * @return array
   */
  public static function getPaymentParameters();

  /**
   * @return array
   */
  public static function getFields();

  /**
   * @param string $field
   *
   * @return mixed
   */
  public function getFieldValue(string $field);


  /**
   * @param $data
   * @return mixed
   */
  public static function getSimplifiedData( $data );

  /**
   * @param $data
   * @return PaymentDataInterface
   */
  public static function fromData($data): PaymentDataInterface;

}
