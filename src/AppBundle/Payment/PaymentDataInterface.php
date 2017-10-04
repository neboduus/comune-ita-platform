<?php

namespace AppBundle\Payment;


interface PaymentDataInterface
{
    /**
     * @return array
     */
    public static function getFields();

    /**
     * @param string $field
     *
     * @return mixed
     */
    public function getFieldValue( string $field );

}