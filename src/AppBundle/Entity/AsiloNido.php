<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;


/**
 * @ORM\Entity
 * @ORM\Table(name="asilo_nido")
 */
class AsiloNido
{
    /**
     * @ORM\Column(type="guid")
     * @ORM\Id
     */
    protected $id;

    /**
     * @var string
     * @ORM\Column(type="string", length=100)
     */
    private $name;

    /**
     * @var string
     * @ORM\Column(type="string" , nullable=true)
     */
    private $schedaInformativa;


    public function __construct()
    {
        if ( !$this->id) {
            $this->id = Uuid::uuid4();
        }
    }

    /**
     * @return UuidInterface
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return AsiloNido
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getSchedaInformativa()
    {
        return $this->schedaInformativa;
    }

    /**
     * @param string $schedaInformativa
     *
     * @return AsiloNido
     */
    public function setSchedaInformativa($schedaInformativa)
    {
        $this->schedaInformativa = $schedaInformativa;

        return $this;
    }

    function __toString()
    {
        return (string)$this->getId();
    }

}