<?php

namespace AppBundle\Protocollo;

use AppBundle\Entity\AllegatoInterface;
use AppBundle\Entity\Pratica;

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

    /**
     * @param Pratica $pratica
     */
    public function sendRispostaToProtocollo(Pratica $pratica);

    /**
     * @param Pratica $pratica
     * @param AllegatoInterface $allegato
     */
    public function sendAllegatoRispostaToProtocollo(Pratica $pratica, AllegatoInterface $allegato);

}
