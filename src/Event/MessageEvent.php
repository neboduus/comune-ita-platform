<?php

namespace App\Event;

use App\Entity\Message;
use Symfony\Contracts\EventDispatcher\Event;

class MessageEvent extends Event
{

  const NAME = 'ocsdc.message.created';

  /**
   * @var Message
   */
  private $item;

  /**
   * @param Message $item
   */
  public function __construct(Message $item)
  {
    $this->item = $item;
  }

  /**
   * @return Message
   */
  public function getItem(): Message
  {
    return $this->item;
  }

}
