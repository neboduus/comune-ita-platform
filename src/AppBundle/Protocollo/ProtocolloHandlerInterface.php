<?php

namespace AppBundle\Protocollo;

use AppBundle\Entity\AllegatoInterface;
use AppBundle\Entity\Pratica;

interface ProtocolloHandlerInterface
{

  const PROTOCOL_EXECUTION_TYPE_INTERNAL = 'internal';
  const PROTOCOL_EXECUTION_TYPE_EXTERNAL = 'external';

  public function getName();

  /**
   * @return mixed
   *
   */
  public function getExecutionType();

  /**
   * @return mixed
   */
  public function getConfigParameters();

  /**
   * @param Pratica $pratica
   */
  public function sendPraticaToProtocollo(Pratica $pratica);

  /**
   * @param Pratica $pratica
   * @param AllegatoInterface $allegato
   */
  public function sendAllegatoToProtocollo(Pratica $pratica, AllegatoInterface $allegato);

  /**
   * @param Pratica $pratica
   */
  public function sendRispostaToProtocollo(Pratica $pratica);

  /**
   * @param Pratica $pratica
   */
  public function sendRitiroToProtocollo(Pratica $pratica);

  /**
   * @param Pratica $pratica
   * @param AllegatoInterface $allegato
   */
  public function sendAllegatoRispostaToProtocollo(Pratica $pratica, AllegatoInterface $allegato);

  /**
   * @param Pratica $pratica
   * @param AllegatoInterface $allegato
   */
  public function sendRichiestaIntegrazioneToProtocollo(Pratica $pratica, AllegatoInterface $allegato);

  /**
   * @param Pratica $pratica
   * @param AllegatoInterface $allegato
   */
  public function sendRispostaIntegrazioneToProtocollo(Pratica $pratica, AllegatoInterface $allegato);

  /**
   * @param Pratica $pratica
   * @param AllegatoInterface $rispostaIntegrazione
   * @param AllegatoInterface $allegato
   */
  public function sendIntegrazioneToProtocollo(Pratica $pratica, AllegatoInterface $rispostaIntegrazione, AllegatoInterface $allegato);
}
