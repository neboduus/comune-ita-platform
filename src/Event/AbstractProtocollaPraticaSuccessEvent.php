<?php

namespace App\Event;

use App\Entity\Pratica;
use Symfony\Contracts\EventDispatcher\Event;

abstract class AbstractProtocollaPraticaSuccessEvent extends Event
{
    /**
     * @var Pratica
     */
    protected $pratica;

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
