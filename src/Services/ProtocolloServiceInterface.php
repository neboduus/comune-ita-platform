<?php

namespace App\Services;

use App\Entity\AllegatoInterface;
use App\Entity\Pratica;

interface ProtocolloServiceInterface
{
  public function protocollaPratica(Pratica $pratica);

  public function protocollaRichiesteIntegrazione(Pratica $pratica);

  public function protocollaAllegatiIntegrazione(Pratica $pratica);

  public function protocollaRisposta(Pratica $pratica);

  public function protocollaRitiro(Pratica $pratica);

  public function protocollaAllegato(Pratica $pratica, AllegatoInterface $allegato);

  public function getHandler();
}
