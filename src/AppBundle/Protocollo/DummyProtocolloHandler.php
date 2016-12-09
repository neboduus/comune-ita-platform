<?php

namespace AppBundle\Protocollo;

use AppBundle\Entity\AllegatoInterface;
use AppBundle\Entity\Pratica;


class DummyProtocolloHandler implements ProtocolloHandlerInterface
{

    /**
     * @param Pratica $pratica
     *
     */
    public function sendPraticaToProtocollo(Pratica $pratica)
    {
        $pratica->setIdDocumentoProtocollo(rand(0,100));
        $pratica->setNumeroProtocollo(rand(100,200));
    }

    /**
     * @param Pratica $pratica
     * @param AllegatoInterface $allegato
     */
    public function sendAllegatoToProtocollo(Pratica $pratica, AllegatoInterface $allegato)
    {
        $pratica->addNumeroDiProtocollo([
            'id' => $allegato->getId(),
            'protocollo' => rand(0,100),
        ]);
    }

}
