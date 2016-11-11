<?php

namespace AppBundle\ScheduledAction;

use AppBundle\Entity\ScheduledAction;

interface ScheduledActionHandlerInterface
{
    public function executeScheduledAction(ScheduledAction $action);
}
