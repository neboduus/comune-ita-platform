<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

/**
 * @ORM\Entity
 * @ORM\Table(name="pratica")
 */
class Pratica
{
    const STATUS_CANCELLED  = 0;
    const STATUS_DRAFT      = 1;
    const STATUS_SUBMITTED  = 2;
    const STATUS_COMPLETE   = 3;
    const STATUS_REGISTERED = 4;
    const STATUS_PENDING    = 5;


    /**
     * @ORM\Column(type="guid")
     * @ORM\Id
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\CPSUser")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false)
     */
    private $user;

    /**
     * @ORM\ManyToOne(targetEntity="Servizio")
     * @ORM\JoinColumn(name="servizio_id", referencedColumnName="id", nullable=false)
     */
    private $servizio;

    /**
     * @ORM\ManyToOne(targetEntity="Ente")
     * @ORM\JoinColumn(name="ente_id", referencedColumnName="id", nullable=true)
     */
    private $ente;

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\OperatoreUser")
     * @ORM\JoinColumn(name="operatore_id", referencedColumnName="id", nullable=true)
     */
    private $operatore;

    /**
     * @ORM\Column(type="integer", name="creation_time")
     */
    private $creationTime;

    /**
     * @ORM\Column(type="integer")
     */
    private $status;


    protected $type;

    public function __construct()
    {
        if ( !$this->id ) {
            $this->id = Uuid::uuid4();
        }
        $this->creationTime = time();
        $this->status = self::STATUS_DRAFT;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return CPSUser
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param $user
     * @return $this
     */
    public function setUser($user)
    {
        $this->user = $user;
        return $this;
    }

    /**
     * @return Servizio
     */
    public function getServizio()
    {
        return $this->servizio;
    }

    /**
     * @param $servizio
     * @return $this
     */
    public function setServizio($servizio)
    {
        $this->servizio = $servizio;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getCreationTime()
    {
        return $this->creationTime;
    }

    /**
     * @param $time
     * @return $this
     */
    public function setCreationTime($time)
    {
        $this->creation_time = $time;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param $status
     * @return $this
     */
    public function setStatus($status)
    {
        $this->status = $status;
        return $this;
    }

    /**
     * @return Ente
     */
    public function getEnte()
    {
        return $this->ente;
    }

    /**
     * @param mixed $ente
     *
     * @return static
     */
    public function setEnte($ente)
    {
        $this->ente = $ente;

        return $this;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param mixed $type
     *
     * @return static
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return OperatoreUser|null
     */
    public function getOperatore()
    {
        return $this->operatore;
    }

    /**
     * @param OperatoreUser $operatore
     * @return Pratica
     */
    public function setOperatore(OperatoreUser $operatore)
    {
        $this->operatore = $operatore;

        return $this;
    }
}
