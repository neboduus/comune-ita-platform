<?php

namespace AppBundle\Services;

use AppBundle\Entity\AllegatoInterface;
use AppBundle\Entity\Pratica;
use AppBundle\Entity\RichiestaIntegrazione;
use AppBundle\Entity\RispostaIntegrazione;
use AppBundle\Protocollo\Exception\AlreadySentException;
use AppBundle\Protocollo\Exception\AlreadyUploadException;
use AppBundle\Protocollo\Exception\ParentNotRegisteredException;

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
    /*if (!$risposta instanceof RispostaOperatore) {
        throw new AlreadySentException();
    }*/

    if ($risposta->getNumeroProtocollo() !== null) {
      throw new AlreadySentException();
    }

    foreach ($pratica->getAllegatiOperatore() as $allegato) {
      $this->validateRispostaUploadFile($pratica, $allegato);
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
