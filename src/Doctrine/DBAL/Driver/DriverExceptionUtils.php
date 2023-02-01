<?php

namespace App\Doctrine\DBAL\Driver;

class DriverExceptionUtils
{

  /*
  SQLSTATE[HY000]: General error: 7 FATAL:  terminating current active connection due to forced scale event
  SSL connection has been closed unexpectedly

  PDOException: SQLSTATE[08006] [7] server closed the connection unexpectedly
	This probably means the server terminated abnormally
	before or while processing the request.

  An exception occurred in driver: SQLSTATE[08006] [7] FATAL:  terminating connection because backend initialization completed past seamless quiet point
  */
  /** @var string[] */
  public static array $goneAwayExceptions = [
    'terminating current active connection due to forced scale event',
    'SSL connection has been closed unexpectedly',
    'server closed the connection unexpectedly',
  ];

  /** @var string[] */
  public static array $goneAwayInUpdateExceptions = [
    'terminating current active connection due to forced scale event',
    'SSL connection has been closed unexpectedly',
    'server closed the connection unexpectedly',
  ];

  /**
   * @param \Exception $e
   *
   * @return bool
   */
  public static function isGoneAwayException(\Exception $e): bool
  {
    $message = $e->getMessage();

    foreach (self::$goneAwayExceptions as $goneAwayException) {
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
  public static function isGoneAwayInUpdateException(\Exception $e): bool
  {
    $message = $e->getMessage();

    foreach (self::$goneAwayInUpdateExceptions as $goneAwayException) {
      if (stripos($message, $goneAwayException) !== false) {
        return true;
      }
    }
    return false;
  }

}
