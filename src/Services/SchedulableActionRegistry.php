<?php

namespace App\Services;

use App\ScheduledAction\ScheduledActionHandlerInterface;

class SchedulableActionRegistry
{
    private $services = [];

    public function registerService(ScheduledActionHandlerInterface $flow, $alias)
    {
        $this->services[$alias] = $flow;
    }

    /**
     * @param string|null $alias
     * @return ScheduledActionHandlerInterface|null
     */
    public function getByName(string $alias = null)
    {
        if ($alias && isset($this->services[$alias])) {
            return $this->services[$alias];
        }

        return null;
    }
}
