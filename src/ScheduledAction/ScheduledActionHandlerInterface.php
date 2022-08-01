<?php

namespace App\ScheduledAction;

use App\Entity\ScheduledAction;

interface ScheduledActionHandlerInterface
{
    public function executeScheduledAction(ScheduledAction $action);
}
