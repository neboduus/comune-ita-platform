<?php

namespace AppBundle\Entity;


interface PaymentPracticeInterface
{

    /**
     * @return array
     */
    public function getPaymentData();

    /**
     * @param array $paymentData
     */
    public function setPaymentData( $paymentData );

}