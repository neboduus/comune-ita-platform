<?php

namespace AppBundle\Services;

use AppBundle\Entity\AllegatoInterface;
use AppBundle\Entity\Pratica;
use AppBundle\Protocollo\Exception\AlreadySentException;
use AppBundle\Protocollo\Exception\AlreadyUploadException;
use AppBundle\Protocollo\Exception\ParentNotRegisteredException;
use AppBundle\Protocollo\Exception\InvalidStatusException;

class AbstractProtocolloService
{
    protected function validatePratica(Pratica $pratica)
    {
        if ($pratica->getStatus() == Pratica::STATUS_DRAFT){
            throw new InvalidStatusException();
        }

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
}
