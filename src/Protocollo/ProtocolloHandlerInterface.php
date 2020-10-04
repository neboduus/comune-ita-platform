<?php

namespace App\Protocollo;

use App\Entity\AllegatoInterface;
use App\Entity\Pratica;

interface ProtocolloHandlerInterface
{

  public function getName();

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
