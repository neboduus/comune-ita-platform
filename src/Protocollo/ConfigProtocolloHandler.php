<?php

namespace App\Protocollo;

use App\Entity\AllegatoInterface;
use App\Entity\Pratica;

class ConfigProtocolloHandler implements ProtocolloHandlerInterface
{
  private $registry;

  private $handlerAlias;

  private $handler;

  public function __construct(ProtocolloHandlerRegistry $registry, $handlerAlias)
  {
    $this->registry = $registry;
    $this->handlerAlias = $handlerAlias;
  }

  private function getHandler()
  {
    if ($this->handler === null){
      $this->handler = $this->registry->getByName($this->handlerAlias);
    }

    return $this->handler;
  }

  public function getName()
  {
    return $this->getHandler()->getName();
  }

  public function getExecutionType()
  {
    if ($this->getHandler() instanceof ProtocolloHandlerInterface) {
      return $this->getHandler()->getExecutionType();
    }
  }

  public function getConfigParameters()
  {
    return $this->getHandler()->getConfigParameters();
  }

  public function sendPraticaToProtocollo(Pratica $pratica)
  {
    return $this->getHandler()->sendPraticaToProtocollo($pratica);
  }

  public function sendAllegatoToProtocollo(Pratica $pratica, AllegatoInterface $allegato)
  {
    return $this->getHandler()->sendAllegatoToProtocollo($pratica, $allegato);
  }

  public function sendRispostaToProtocollo(Pratica $pratica)
  {
    return $this->getHandler()->sendRispostaToProtocollo($pratica);
  }

  public function sendRitiroToProtocollo(Pratica $pratica)
  {
    return $this->getHandler()->sendRitiroToProtocollo($pratica);
  }

  public function sendAllegatoRispostaToProtocollo(Pratica $pratica, AllegatoInterface $allegato)
  {
    return $this->getHandler()->sendAllegatoRispostaToProtocollo($pratica, $allegato);
  }

  public function sendRichiestaIntegrazioneToProtocollo(Pratica $pratica, AllegatoInterface $allegato)
  {
    return $this->getHandler()->sendRichiestaIntegrazioneToProtocollo($pratica, $allegato);
  }

  public function sendAllegatoRichiestaIntegrazioneToProtocollo(Pratica $pratica, AllegatoInterface $richiestaIntegrazione, AllegatoInterface $allegato)
  {
    return $this->getHandler()->sendAllegatoRichiestaIntegrazioneToProtocollo($pratica, $richiestaIntegrazione, $allegato);
  }

  public function sendRispostaIntegrazioneToProtocollo(Pratica $pratica, AllegatoInterface $allegato)
  {
    return $this->getHandler()->sendRispostaIntegrazioneToProtocollo($pratica, $allegato);
  }

  public function sendIntegrazioneToProtocollo(
    Pratica $pratica,
    AllegatoInterface $rispostaIntegrazione,
    AllegatoInterface $allegato
  ) {
    return $this->getHandler()->sendIntegrazioneToProtocollo($pratica, $rispostaIntegrazione, $allegato);
  }

}
