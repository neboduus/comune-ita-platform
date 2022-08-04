<?php

namespace App\Event;

use App\Entity\Pratica;
use Symfony\Contracts\EventDispatcher\Event;

class ProtocollaAllegatoSuccessEvent extends Event
{
    /**
     * @var Pratica
     */
    private $pratica;

    public function __construct(Pratica $pratica)
    {
        $this->pratica = $pratica;
    }

    /**
     * @return Pratica
     */
    public function getPratica()
    {
        return $this->pratica;
    }

}
