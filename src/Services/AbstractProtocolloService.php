<?php

namespace App\Services;

use App\Entity\AllegatoInterface;
use App\Entity\Pratica;
use App\Entity\RichiestaIntegrazione;
use App\Entity\RispostaIntegrazione;
use App\Protocollo\Exception\AlreadySentException;
use App\Protocollo\Exception\AlreadyUploadException;
use App\Protocollo\Exception\ParentNotRegisteredException;

class AbstractProtocolloService
{
  /**
   * @param Pratica $pratica
   * @throws AlreadySentException
   */
  protected function validatePratica(Pratica $pratica)
  {
    $mainSent = $pratica->getNumeroProtocollo() !== null;

    $allegati = $pratica->getAllegati();
    $countSentAllegati = 0;
    foreach ($allegati as $allegato) {
      try {
        $this->validateUploadFile($pratica, $allegato);
      } catch (AlreadyUploadException $e) {
        $countSentAllegati++;
      }
    }

    $moduliCompilati = $pratica->getModuliCompilati();
    $countSentModuliCompilati = 0;
    foreach ($moduliCompilati as $allegato) {
      try {
        $this->validateUploadFile($pratica, $allegato);
      } catch (AlreadyUploadException $e) {
        $countSentModuliCompilati++;
      }
    }

    if ($mainSent && $countSentAllegati == $allegati->count() && $countSentModuliCompilati == $moduliCompilati->count()){
      throw new AlreadySentException();
    }
  }

  /**
   * @param Pratica $pratica
   * @param AllegatoInterface $allegato
   * @throws AlreadyUploadException
   */
  protected function validateUploadFile(Pratica $pratica, AllegatoInterface $allegato)
  {
    $alreadySent = false;
    foreach ($pratica->getNumeriProtocollo() as $item) {
      $item = (array)$item;
      if ($item['id'] == $allegato->getId()) {
        $alreadySent = true;
      }
    }

    if ($alreadySent) {
      throw new AlreadyUploadException();
    }
  }

  protected function validatePraticaForUploadFile(Pratica $pratica)
  {
    if ($pratica->getNumeroProtocollo() === null) {
      throw new ParentNotRegisteredException();
    }
  }

  protected function validateRichiestaIntegrazione(Pratica $pratica, RichiestaIntegrazione $richiesta)
  {
    if ($richiesta->getNumeroProtocollo() !== null) {
      throw new AlreadySentException();
    }
  }

  protected function validateRichiestaIntegrazioneUploadFile(RichiestaIntegrazione $richiesta, AllegatoInterface $allegato)
  {
    $alreadySent = false;
    foreach ($richiesta->getNumeriProtocollo() as $item) {
      $item = (array)$item;
      if ($item['id'] == $allegato->getId()) {
        $alreadySent = true;
      }
    }

    if ($alreadySent) {
      throw new AlreadyUploadException();
    }
  }

  /**
   * @param RispostaIntegrazione $risposta
   * @throws AlreadySentException
   */
  protected function validateRispostaIntegrazione(RispostaIntegrazione $risposta)
  {
    if ($risposta->getNumeroProtocollo() !== null) {
      throw new AlreadySentException();
    }
  }

  protected function validateRisposta(Pratica $pratica)
  {
    $risposta = $pratica->getRispostaOperatore();
    $mainSent = $risposta->getNumeroProtocollo() !== null;
    $mainSentAttachment = $this->validateRispostaUploadFile($pratica, $pratica->getRispostaOperatore());

    $allegati = $pratica->getAllegatiOperatore();
    $countSentAllegati = 0;
    foreach ($allegati as $allegato) {
      try {
        $this->validateRispostaUploadFile($pratica, $allegato);
      } catch (AlreadyUploadException $e) {
        $countSentAllegati++;
      }
    }

    if ($mainSent && $mainSentAttachment && $countSentAllegati == $allegati->count()){
      throw new AlreadySentException();
    }

  }

  protected function validateRispostaUploadFile(Pratica $pratica, AllegatoInterface $allegato)
  {
    $risposta = $pratica->getRispostaOperatore();
    $alreadySent = false;
    foreach ($risposta->getNumeriProtocollo() as $item) {
      $item = (array)$item;
      if ($item['id'] == $allegato->getId()) {
        $alreadySent = true;
      }
    }

    if ($alreadySent) {
      throw new AlreadyUploadException();
    }
  }

  protected function validateRitiro(Pratica $pratica)
  {
    $ritiro = $pratica->getWithdrawAttachment();

    if ($ritiro->getNumeroProtocollo() !== null) {
      throw new AlreadySentException();
    }
  }
}
