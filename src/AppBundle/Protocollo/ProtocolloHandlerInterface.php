<?php

namespace AppBundle\Protocollo;

use AppBundle\Entity\Pratica;
use AppBundle\Entity\AllegatoInterface;

interface ProtocolloHandlerInterface
{
    /**
     * @param Pratica $pratica
     */
    public function sendPraticaToProtocollo(Pratica $pratica);

    /**
     * @param Pratica $pratica
     * @param AllegatoInterface $allegato
     */
    public function sendAllegatoToProtocollo(Pratica $pratica, AllegatoInterface $allegato);

}
