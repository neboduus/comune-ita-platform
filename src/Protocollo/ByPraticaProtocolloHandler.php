<?php

namespace App\Protocollo;

use App\Entity\AllegatoInterface;
use App\Entity\GiscomPratica;
use App\Entity\Pratica;

class ByPraticaProtocolloHandler implements ProtocolloHandlerInterface, PredisposedProtocolHandlerInterface
{
  private $registry;

  private $currentHandler;

  public function __construct(ProtocolloHandlerRegistry $registry)
  {
    $this->registry = $registry;
  }

  /**
   * @param Pratica $pratica
   * @return ProtocolloHandlerInterface
   */
  public function getHandler(Pratica $pratica)
  {
    // Per migrazione soft, rimuovere appena eseguiti script
    if ($pratica instanceof GiscomPratica) {
      $this->currentHandler = $this->registry->getByName('pitre');
      return $this->currentHandler;
    }

    $this->currentHandler = $this->registry->getByName($pratica->getServizio()->getProtocolHandler());
    return $this->currentHandler;
  }

  public function getExecutionType()
  {
    if ($this->currentHandler instanceof ProtocolloHandlerInterface) {
      return $this->currentHandler->getExecutionType();
    }
  }

  public function getName()
  {
    if ($this->currentHandler instanceof ProtocolloHandlerInterface) {
      return $this->currentHandler->getName();
    }

    return null;
  }

  public function getConfigParameters()
  {
    if ($this->currentHandler instanceof ProtocolloHandlerInterface) {
      return $this->currentHandler->getConfigParameters();
    }

    return [];
  }

  public function sendPraticaToProtocollo(Pratica $pratica)
  {
    return $this->getHandler($pratica)->sendPraticaToProtocollo($pratica);
  }

  public function sendAllegatoToProtocollo(Pratica $pratica, AllegatoInterface $allegato)
  {
    return $this->getHandler($pratica)->sendAllegatoToProtocollo($pratica, $allegato);
  }

  public function sendRispostaToProtocollo(Pratica $pratica)
  {
    return $this->getHandler($pratica)->sendRispostaToProtocollo($pratica);
  }

  public function sendRitiroToProtocollo(Pratica $pratica)
  {
    return $this->getHandler($pratica)->sendRitiroToProtocollo($pratica);
  }

  public function sendAllegatoRispostaToProtocollo(Pratica $pratica, AllegatoInterface $allegato)
  {
    return $this->getHandler($pratica)->sendAllegatoRispostaToProtocollo($pratica, $allegato);
  }

  public function sendRichiestaIntegrazioneToProtocollo(Pratica $pratica, AllegatoInterface $allegato)
  {
    return $this->getHandler($pratica)->sendRichiestaIntegrazioneToProtocollo($pratica, $allegato);
  }

  public function sendRispostaIntegrazioneToProtocollo(Pratica $pratica, AllegatoInterface $allegato)
  {
    return $this->getHandler($pratica)->sendRispostaIntegrazioneToProtocollo($pratica, $allegato);
  }

  public function sendIntegrazioneToProtocollo(Pratica $pratica, AllegatoInterface $rispostaIntegrazione, AllegatoInterface $allegato)
  {
    return $this->getHandler($pratica)->sendIntegrazioneToProtocollo($pratica, $rispostaIntegrazione, $allegato);
  }

  // Fixme: verificare come spostare negli handler
  public function protocolPredisposed(Pratica $pratica)
  {
    if ($this->getHandler($pratica) instanceof PredisposedProtocolHandlerInterface) {
      return $this->getHandler($pratica)->protocolPredisposed($pratica);
    }
  }

  public function protocolPredisposedAttachment(Pratica $pratica, AllegatoInterface $attachment)
  {
    if ($this->getHandler($pratica) instanceof PredisposedProtocolHandlerInterface) {
      return $this->getHandler($pratica)->protocolPredisposedAttachment($pratica, $attachment);
    }
  }

}
