<?php

namespace App\Event;

use Symfony\Component\EventDispatcher\Event;

class KafkaEvent extends Event
{

  const NAME = 'ocsdc.kafka';

  private $item;

  public function __construct($item)
  {
    $this->item = $item;
  }

  public function getItem()
  {
    return $this->item;
  }

}
