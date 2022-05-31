<?php


namespace AppBundle\Payment;


class GatewayCollection
{
  private $availablePaymentGateways;

  private $handlers;

  public function __construct(iterable $handlers, $paymentGatewaysParameters)
  {
    $handlers = iterator_to_array($handlers);
    foreach ($handlers as $handler) {
      $this->handlers[$handler->getIdentifier()] = $handler;
    }

    $this->setAvailablePaymentGateways($paymentGatewaysParameters);
  }

  public function getAvailablePaymentGateways()
  {
    return $this->availablePaymentGateways;
  }

  private function setAvailablePaymentGateways($paymentGatewaysParameters)
  {
    foreach ($paymentGatewaysParameters as $k => $v) {
      if (isset($v['enabled']) && $v['enabled']) {
        $this->availablePaymentGateways[$k] = $v;
      }
    }
  }

  /**
   * @return mixed
   */
  public function getHandlers()
  {
    return $this->handlers;
  }

  /**
   * @param mixed $handlers
   */
  public function setHandlers($handlers): void
  {
    $this->handlers = $handlers;
  }


}
