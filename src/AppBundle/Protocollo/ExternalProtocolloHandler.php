<?php


namespace AppBundle\Protocollo;


use AppBundle\Entity\AllegatoInterface;
use AppBundle\Entity\Pratica;

class ExternalProtocolloHandler implements ProtocolloHandlerInterface
{
  public function getName()
  {
    return 'Esterno';
  }

  public function getExecutionType()
  {
    return self::PROTOCOL_EXECUTION_TYPE_EXTERNAL;
  }

  public function getConfigParameters()
  {
    return false;
  }

  public function sendPraticaToProtocollo(Pratica $pratica)
  {
    // TODO: Implement sendPraticaToProtocollo() method.
  }

  public function sendAllegatoToProtocollo(Pratica $pratica, AllegatoInterface $allegato)
  {
    // TODO: Implement sendAllegatoToProtocollo() method.
  }

  public function sendRispostaToProtocollo(Pratica $pratica)
  {
    // TODO: Implement sendRispostaToProtocollo() method.
  }

  public function sendRitiroToProtocollo(Pratica $pratica)
  {
    // TODO: Implement sendRitiroToProtocollo() method.
  }

  public function sendAllegatoRispostaToProtocollo(Pratica $pratica, AllegatoInterface $allegato)
  {
    // TODO: Implement sendAllegatoRispostaToProtocollo() method.
  }

  public function sendRichiestaIntegrazioneToProtocollo(Pratica $pratica, AllegatoInterface $allegato)
  {
    // TODO: Implement sendRichiestaIntegrazioneToProtocollo() method.
  }

  public function sendRispostaIntegrazioneToProtocollo(Pratica $pratica, AllegatoInterface $allegato)
  {
    // TODO: Implement sendRispostaIntegrazioneToProtocollo() method.
  }

  public function sendIntegrazioneToProtocollo(
    Pratica $pratica,
    AllegatoInterface $rispostaIntegrazione,
    AllegatoInterface $allegato
  ) {
    // TODO: Implement sendIntegrazioneToProtocollo() method.
  }


}
