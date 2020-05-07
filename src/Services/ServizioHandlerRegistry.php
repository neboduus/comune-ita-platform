<?php

namespace App\Services;

use App\Handlers\Servizio\ServizioHandlerInterface;

class ServizioHandlerRegistry
{
    private $handlers = [];

    public function registerHandler(ServizioHandlerInterface $flow, $alias)
    {
        $this->handlers[$alias] = $flow;
    }

    /**
     * @param string $alias
     * @return ServizioHandlerInterface
     */
    public function getByName(string $alias = null)
    {
        if ($alias && isset($this->handlers[$alias])) {
            return $this->handlers[$alias];
        }

        return $this->handlers['default'];
    }
}
