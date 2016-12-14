<?php

namespace AppBundle\Services;

use AppBundle\Entity\AllegatoInterface;
use AppBundle\Entity\Pratica;

interface ProtocolloServiceInterface
{
    public function protocollaPratica(Pratica $pratica);

    public function protocollaAllegatiOperatore(Pratica $pratica);

    public function protocollaAllegato(Pratica $pratica, AllegatoInterface $allegato);

    public function getHandler();
}
