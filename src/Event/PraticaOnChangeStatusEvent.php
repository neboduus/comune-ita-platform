<?php

namespace App\Event;

use App\Entity\Pratica;
use Symfony\Contracts\EventDispatcher\Event;

class PraticaOnChangeStatusEvent extends Event
{

  const NAME = 'ocsdc.pratica.on_status_change';

  /**
   * @var Pratica
   */
  private $pratica;

  /**
   * @var string
   */
  private $newStateIdentifier;

  /**
   * @var string
   */
  private $oldStateIdentifier;


  public function __construct(Pratica $pratica, $newStateIdentifier, $oldStateIdentifier)
  {
    $this->pratica = $pratica;
    $this->newStateIdentifier = $newStateIdentifier;
    $this->oldStateIdentifier = $oldStateIdentifier;
  }

  /**
   * @return Pratica
   */
  public function getPratica()
  {
    return $this->pratica;
  }

  /**
   * @return string
   */
  public function getNewStateIdentifier()
  {
    return $this->newStateIdentifier;
  }

  /**
   * @return string
   */
  public function getOldStateIdentifier()
  {
    return $this->oldStateIdentifier;
  }

}
