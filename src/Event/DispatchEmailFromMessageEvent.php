<?php

namespace App\Event;

use App\Entity\Message;
use Symfony\Component\EventDispatcher\Event;

class DispatchEmailFromMessageEvent extends Event
{

  const EVENT_IDENTIFIER = 'ocsdc.event.dispatch_email_from_message';

  /**
   * @var Message
   */
  private $message;

  public function __construct(Message $message)
  {
    $this->message = $message;
  }

  /**
   * @return Message
   */
  public function getMessage(): Message
  {
    return $this->message;
  }

}
