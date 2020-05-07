<?php

namespace App\Payment;

class PaymentGatewayRegistry
{
    private $handlers = [];

    public function registerPaymentGateway(PaymentDataInterface $paymentData)
    {
        $fcqn = get_class($paymentData);
        $this->handlers[$fcqn] = $paymentData;
    }

    /**
     * @param string $fcqn
     * @return PaymentDataInterface
     * @throws \Exception
     */
    public function get(string $fcqn)
    {
        if (isset($this->handlers[$fcqn])) {
            return $this->handlers[$fcqn];
        }

        throw new \Exception("Paymet gateway $fcqn not found");
    }
}
