<?php

namespace AppBundle\Doctrine\DBAL\Driver\PDOPgSql;

use AppBundle\Doctrine\DBAL\Driver\ServerGoneAwayExceptionsAwareInterface;

/**
 * Class Driver.
 */
class Driver extends \Doctrine\DBAL\Driver\PDOPgSql\Driver implements ServerGoneAwayExceptionsAwareInterface
{
  /** @var string[] */
  protected $goneAwayExceptions = [
    'Database server has gone away',
    'Lost connection to Database server during query',
  ];

  /** @var string[] */
  protected $goneAwayInUpdateExceptions = [
    'Database server has gone away',
  ];

  /**
   * @param \Exception $e
   *
   * @return bool
   */
  public function isGoneAwayException(\Exception $e)
  {
    $message = $e->getMessage();

    foreach ($this->goneAwayExceptions as $goneAwayException) {
      if (stripos($message, $goneAwayException) !== false) {
        return true;
      }
    }
    return false;
  }

  /**
   * @param \Exception $e
   *
   * @return bool
   */
  public function isGoneAwayInUpdateException(\Exception $e)
  {
    $message = $e->getMessage();

    foreach ($this->goneAwayInUpdateExceptions as $goneAwayException) {
      if (stripos($message, $goneAwayException) !== false) {
        return true;
      }
    }
    return false;
  }
}
