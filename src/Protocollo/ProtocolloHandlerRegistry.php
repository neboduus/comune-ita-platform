<?php

namespace App\Protocollo;

class ProtocolloHandlerRegistry
{
  private $handlers = [];

  public function registerHandler(ProtocolloHandlerInterface $handler, $alias)
  {
    $this->handlers[$alias] = $handler;
  }

  /**
   * @param string $alias
   * @return ProtocolloHandlerInterface
   */
  public function getByName(string $alias)
  {
    if (isset($this->handlers[$alias])) {
      return $this->handlers[$alias];
    }

    throw new \InvalidArgumentException("Protocollo handler $alias not found");
  }
}
