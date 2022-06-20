<?php

namespace AppBundle\Doctrine\DBAL;

use Doctrine\DBAL\Driver\Connection as DriverConnection;

interface ConnectionInterface extends DriverConnection
{
  public function canTryAgain($attempt, $ignoreTransactionLevel = false);
}
