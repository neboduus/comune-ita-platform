<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Gedmo\Mapping\Annotation as Gedmo;
use Ramsey\Uuid\UuidInterface;

/**
 * @ORM\Entity
 * @ORM\Table(name="servizio")
 */
class Servizio
{

    const STATUS_CANCELLED = 0;
    const STATUS_AVAILABLE = 1;
    const STATUS_SUSPENDED = 2;

    /**
     * @ORM\Column(type="guid")
     * @ORM\Id
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=100)
     */
    private $name;

    /**
     * @var string
     *
     * @Gedmo\Slug(fields={"name"})
     * @ORM\Column(type="string", length=100, unique=true)
     */
    private $slug;

    /**
     * @ORM\ManyToMany(targetEntity="Ente", cascade={"remove"})
     * @ORM\JoinTable(
     *     name="servizio_enti",
     *     joinColumns={@ORM\JoinColumn(name="servizio_id", referencedColumnName="id")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="ente_id", referencedColumnName="id")}
     * )
     */
    private $enti;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=true)
     */
    private $area;

    /**
     * @var string
     * @ORM\Column(type="text", nullable=true)
     */
    private $description;

    /**
     * @var string
     * @ORM\Column(type="text", nullable=true)
     */
    private $testo_istruzioni;

    /**
     * @ORM\Column(type="integer")
     */
    private $status;

    /**
     * @var string Fully Qualified Class Name
     * @ORM\Column(type="string")
     */
    private $praticaFCQN;

    /**
     * @var string Fully Qualified Class Name
     * @ORM\Column(type="string")
     */
    private $praticaFlowServiceName;

    /**
     * Servizio constructor.
     */
    public function __construct()
    {
        if ( !$this->id) {
            $this->id = Uuid::uuid4();
        }
        $this->status = self::STATUS_AVAILABLE;
    }

    /**
     * @return UuidInterface
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
     * @param string $name
     *
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getSlug()
    {
        return $this->slug;
    }

    /**
     * @param string $slug
     *
     * @return $this
     */
    public function setSlug($slug)
    {
        $this->slug = $slug;

        return $this;
    }

    /**
     * @return Ente[]
     */
    public function getEnti()
    {
        return $this->enti;
    }

    /**
     * @param mixed $enti
     *
     * @return Servizio
     */
    public function setEnti($enti)
    {
        $this->enti = $enti;

        return $this;
    }

    /**
     * @return string
     */
    public function getArea()
    {
        return $this->area;
    }

    /**
     * @param string $area
     *
     * @return $this
     */
    public function setArea($area)
    {
        $this->area = $area;

        return $this;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $description
     *
     * @return $this
     */
    public function setDescription(string $description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return string
     */
    public function getTestoIstruzioni()
    {
        return $this->testo_istruzioni;
    }

    /**
     * @param string $testo_istruzioni
     *
     * @return Servizio
     */
    public function setTestoIstruzioni($testo_istruzioni)
    {
        $this->testo_istruzioni = $testo_istruzioni;

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
     *
     * @return $this
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * @return string
     */
    public function getPraticaFCQN()
    {
        return $this->praticaFCQN;
    }

    /**
     * @param string $praticaFCQN
     *
     * @return Servizio
     */
    public function setPraticaFCQN($praticaFCQN)
    {
        $this->praticaFCQN = $praticaFCQN;

        return $this;
    }

    /**
     * @return string
     */
    public function getPraticaFlowServiceName()
    {
        return $this->praticaFlowServiceName;
    }

    /**
     * @param string $praticaFlowServiceName
     *
     * @return Servizio
     */
    public function setPraticaFlowServiceName($praticaFlowServiceName)
    {
        $this->praticaFlowServiceName = $praticaFlowServiceName;

        return $this;
    }


}
