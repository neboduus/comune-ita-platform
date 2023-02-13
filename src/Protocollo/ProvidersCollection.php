<?php


namespace App\Protocollo;


class ProvidersCollection
{
  private $availableRegisterProviders;

  private $handlers;

  public function __construct(iterable $handlers, $registerProvidersParameters)
  {
    $handlers = iterator_to_array($handlers);
    foreach ($handlers as $handler) {
      $this->handlers[$handler->getIdentifier()] = $handler;
    }

    $this->setAvailableRegisterProviders($registerProvidersParameters);
  }

  public function getAvailableRegisterProviders()
  {
    return $this->availableRegisterProviders;
  }

  private function setAvailableRegisterProviders($registerProvidersParameters)
  {
    foreach ($registerProvidersParameters as $k => $v) {
      if (isset($v['enabled']) && $v['enabled']) {
        $this->availableRegisterProviders[$k] = $v;
        if (isset($this->handlers[$v['handler']])) {
          $this->availableRegisterProviders[$k]['handler'] = $this->handlers[$v['handler']];
        }
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
