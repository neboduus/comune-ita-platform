<?php

namespace App\Event;

use Symfony\Contracts\EventDispatcher\Event;

class SecurityEvent extends Event
{

  const NAME = 'ocsdc.security';

  private string $type;
  private $subject;

  public function __construct(string $type, $subject = null)
  {
    $this->type = $type;
    $this->subject = $subject;
  }

  /**
   * @return string
   */
  public function getType(): string
  {
    return $this->type;
  }

  public function getSubject()
  {
    return $this->subject;
  }

}
