<?php

namespace AppBundle\Services;

use AppBundle\Entity\AllegatoInterface;
use AppBundle\Entity\Pratica;
use AppBundle\Entity\RichiestaIntegrazione;
use AppBundle\Entity\RispostaOperatore;
use AppBundle\Protocollo\Exception\AlreadySentException;
use AppBundle\Protocollo\Exception\AlreadyUploadException;
use AppBundle\Protocollo\Exception\ParentNotRegisteredException;

class AbstractProtocolloService
{
    protected function validatePratica(Pratica $pratica)
    {
        if ($pratica->getNumeroProtocollo() !== null) {
            throw new AlreadySentException();
        }

        foreach ($pratica->getAllegati() as $allegato) {
            $this->validateUploadFile($pratica, $allegato);
        }
    }

    protected function validatePraticaForUploadFile(Pratica $pratica)
    {
        if ($pratica->getNumeroProtocollo() === null) {
            throw new ParentNotRegisteredException();
        }
    }

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

    protected function validateRichiestaIntegrazione(Pratica $pratica, RichiestaIntegrazione $richiesta)
    {
        if ($richiesta->getNumeroProtocollo() !== null) {
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

    protected function validateRitiro(Pratica $pratica)
    {
      $ritiro = $pratica->getWithdrawAttachment();

      if ($ritiro->getNumeroProtocollo() !== null) {
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
}
