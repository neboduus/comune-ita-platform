<?php

namespace App\Handlers\Servizio;

class ForbiddenAccessException extends \Exception
{
  private $parameters = [];

  public function __construct($message = "", $parameters = [], $code = 0, \Throwable $previous = null)
  {
    $this->parameters = $parameters;
    parent::__construct($message, $code, $previous);
  }

  /**
   * @return array
   */
  public function getParameters(): array
  {
    return $this->parameters;
  }

}
