<?php

namespace App\Services;

use App\Entity\AllegatoInterface;
use App\Entity\Pratica;
use App\Entity\RichiestaIntegrazione;
use App\Protocollo\Exception\AlreadySentException;
use App\Protocollo\Exception\AlreadyUploadException;
use App\Protocollo\Exception\ParentNotRegisteredException;

class AbstractProtocolloService
{
    /**
     * @param Pratica $pratica
     * @throws AlreadySentException
     * @throws AlreadyUploadException
     */
    protected function validatePratica(Pratica $pratica)
    {
        if ($pratica->getNumeroProtocollo() !== null) {
            throw new AlreadySentException();
        }

        foreach ($pratica->getAllegati() as $allegato) {
            $this->validateUploadFile($pratica, $allegato);
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

    /**
     * @param Pratica $pratica
     * @throws ParentNotRegisteredException
     */
    protected function validatePraticaForUploadFile(Pratica $pratica)
    {
        if ($pratica->getNumeroProtocollo() === null) {
            throw new ParentNotRegisteredException();
        }
    }

    /**
     * @param RichiestaIntegrazione $richiesta
     * @throws AlreadySentException
     */
    protected function validateRichiestaIntegrazione(RichiestaIntegrazione $richiesta)
    {
        if ($richiesta->getNumeroProtocollo() !== null) {
            throw new AlreadySentException();
        }
    }

    /**
     * @param Pratica $pratica
     * @throws AlreadySentException
     * @throws AlreadyUploadException
     */
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

    /**
     * @param Pratica $pratica
     * @param AllegatoInterface $allegato
     * @throws AlreadyUploadException
     */
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
