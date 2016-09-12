<?php

namespace AppBundle\Entity;

use AppBundle\Entity\AsiloNido;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Gedmo\Mapping\Annotation as Gedmo;
use Ramsey\Uuid\UuidInterface;

/**
 * @ORM\Entity
 * @ORM\Table(name="ente")
 */
class Ente
{
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
     * @ORM\ManyToMany(targetEntity="AsiloNido", cascade={"remove"})
     * @ORM\JoinTable(
     *     name="ente_asili",
     *     joinColumns={@ORM\JoinColumn(name="ente_id", referencedColumnName="id")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="asilo_id", referencedColumnName="id")}
     * )
     */
    private $asili;

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
     * @return AsiloNido[]
     */
    public function getAsili()
    {
        return $this->asili;
    }

    /**
     * @param AsiloNido[] $asili
     *
     * @return $this
     */
    public function setAsili($asili)
    {
        $this->asili = $asili;

        return $this;
    }

    function __toString()
    {
        return (string)$this->getId();
    }


}
