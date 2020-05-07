<?php

namespace App\Protocollo;

use App\Services\InstanceService;

class ProtocolloHandlerRegistry
{
    private $instanceService;

    private $handlers;

    public function __construct(InstanceService $instanceService)
    {
        $this->instanceService = $instanceService;
        $this->handlers = [];
    }

    public function registerHandler(ProtocolloHandlerInterface $handler, $alias)
    {
        $this->handlers[$alias] = $handler;
    }

    public function getTenantHandler()
    {
        if ($this->instanceService->hasTenant()) {
            $handler = $this->instanceService->getTenant()->getProtocolloHandler();
            return $this->getHandler($handler);
        }

        return null;
    }

    public function getHandler($alias)
    {
        if (array_key_exists($alias, $this->handlers)) {
            return $this->handlers[$alias];
        }

        return null;
    }
}
