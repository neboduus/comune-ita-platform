<?php

namespace AppBundle\Event;

use AppBundle\Entity\Pratica;
use Symfony\Component\EventDispatcher\Event;

class PraticaOnChangeStatusEvent extends Event
{
    /**
     * @var Pratica
     */
    private $pratica;

    /**
     * @var string
     */
    private $newStateIdentifier;


    public function __construct(Pratica $pratica, $newStateIdentifier)
    {
        $this->pratica = $pratica;
        $this->newStateIdentifier = $newStateIdentifier;
    }

    /**
     * @return Pratica
     */
    public function getPratica()
    {
        return $this->pratica;
    }

    /**
     * @return string
     */
    public function getNewStateIdentifier()
    {
        return $this->newStateIdentifier;
    }

}
