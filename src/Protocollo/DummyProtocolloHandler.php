<?php

namespace App\Protocollo;

use App\Entity\AllegatoInterface;
use App\Entity\Pratica;

class DummyProtocolloHandler implements ProtocolloHandlerInterface
{

  public function getName()
  {
    return 'Dummy';
  }

  public function getExecutionType()
  {
    return self::PROTOCOL_EXECUTION_TYPE_INTERNAL;
  }

  public function getConfigParameters()
  {
    return false;
  }

  /**
   * @param Pratica $pratica
   *
   */
  public function sendPraticaToProtocollo(Pratica $pratica)
  {
    $pratica->setIdDocumentoProtocollo($pratica->getId());
    $pratica->setNumeroProtocollo($pratica->getId());
    $pratica->setNumeroFascicolo($pratica->getId());
  }

  /**
   * @param Pratica $pratica
   * @param AllegatoInterface $allegato
   */
  public function sendAllegatoToProtocollo(Pratica $pratica, AllegatoInterface $allegato)
  {
    $pratica->addNumeroDiProtocollo([
      'id' => $allegato->getId(),
      'protocollo' => 'a-' . $allegato->getId(),
    ]);
  }

  /**
   * @param Pratica $pratica
   * @param AllegatoInterface $richiesta
   */
  public function sendRichiestaIntegrazioneToProtocollo(Pratica $pratica, AllegatoInterface $richiesta)
  {
    $pratica->addNumeroDiProtocollo([
      'id' => $richiesta->getId(),
      'protocollo' => 'r-' . $richiesta->getId(),
    ]);
  }

  public function sendRispostaIntegrazioneToProtocollo(Pratica $pratica, AllegatoInterface $allegato)
  {
    $pratica->addNumeroDiProtocollo([
      'id' => $allegato->getId(),
      'protocollo' => 'r-' . $allegato->getId(),
    ]);
  }

  /**
   * @param Pratica $pratica
   * @param AllegatoInterface $allegato
   */
  public function sendIntegrazioneToProtocollo(Pratica $pratica, AllegatoInterface $rispostaIntegrazione, AllegatoInterface $allegato)
  {
    $pratica->addNumeroDiProtocollo([
      'id' => $allegato->getId(),
      'protocollo' => 'int-' . $allegato->getId(),
    ]);
    $allegato->setNumeroProtocollo('int-' . $allegato->getId());
    $allegato->setIdDocumentoProtocollo('int-' . $allegato->getId());
  }

  public function sendAllegatoRichiestaIntegrazioneToProtocollo(Pratica $pratica, AllegatoInterface $richiestaIntegrazione, AllegatoInterface $allegato)
  {
    $richiestaIntegrazione->addNumeroDiProtocollo([
      'id' => $allegato->getId(),
      'protocollo' => 'int-' . $allegato->getId(),
    ]);
    $allegato->setIdDocumentoProtocollo('int-' . $allegato->getId());
  }


  public function sendRispostaToProtocollo(Pratica $pratica)
  {
    $risposta = $pratica->getRispostaOperatore();
    $risposta->setNumeroProtocollo('rnp-' . $risposta->getId());
    $risposta->setIdDocumentoProtocollo('rdp-' . $risposta->getId());
  }

  public function sendRitiroToProtocollo(Pratica $pratica)
  {
    // TODO: Implement sendRitiroToProtocollo() method.
  }


  public function sendAllegatoRispostaToProtocollo(Pratica $pratica, AllegatoInterface $allegato)
  {
    $risposta = $pratica->getRispostaOperatore();
    $risposta->addNumeroDiProtocollo([
      'id' => $allegato->getId(),
      'protocollo' => 'a-' . $allegato->getId(),
    ]);
  }

}
