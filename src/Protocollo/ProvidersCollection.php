<?php


namespace App\Protocollo;


use App\Entity\Pratica;

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

  public function getHandlerByPratica(Pratica $pratica)
  {
    $providerIdentifier = $pratica->getServizio()->getProtocolHandler();
    if (isset($this->availableRegisterProviders[$providerIdentifier])) {
      return $this->availableRegisterProviders[$providerIdentifier]['handler'];
    }

    throw new \InvalidArgumentException("Protocollo handler $providerIdentifier not found");
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
