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
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false)
     */
    private $user;

    /**
     * @ORM\ManyToOne(targetEntity="Servizio")
     * @ORM\JoinColumn(name="servizio_id", referencedColumnName="id", nullable=false)
     */
    private $servizio;

    /**
     * @ORM\Column(type="string")
     */
    private $name;

    /**
     * @ORM\Column(type="integer", name="creation_time")
     */
    private $creationTime;

    /**
     * @ORM\Column(type="integer")
     */
    private $status;

    /**
     * Pratica constructor.
     */
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
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param $name
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return mixed
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
     * @return mixed
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
        return $this->creation_time;
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

}
