<?php

namespace AppBundle\Services;

use AppBundle\Entity\AllegatoInterface;
use AppBundle\Entity\Pratica;

interface ProtocolloServiceInterface
{
  public function protocollaPratica(Pratica $pratica);

  public function protocollaRichiesteIntegrazione(Pratica $pratica);

  public function protocollaAllegatiIntegrazione(Pratica $pratica);

  public function protocollaRisposta(Pratica $pratica);

  public function protocollaAllegato(Pratica $pratica, AllegatoInterface $allegato);

  public function getHandler();
}
