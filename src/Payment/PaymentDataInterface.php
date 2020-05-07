<?php

namespace App\Payment;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

interface PaymentDataInterface extends EventSubscriberInterface
{
    /**
     * @return array
     */
    public static function getPaymentParameters();

    /**
     * @return array
     */
    public static function getFields();

    /**
     * @param $data
     * @return PaymentDataInterface
     */
    public static function fromData($data): PaymentDataInterface;

    /**
     * @param string $field
     *
     * @return mixed
     */
    public function getFieldValue(string $field);

    /**
     * @return string json
     */
    public function toJson();
}
