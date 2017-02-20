<?php

namespace AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;


/**
 * @ORM\Entity
 * @ORM\Table(name="categoria")
 * @ORM\HasLifecycleCallbacks
 */
class Categoria
{
    /**
     * @ORM\Column(type="guid")
     * @ORM\Id
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @var string
     *
     * @Gedmo\Slug(fields={"name"})
     * @ORM\Column(type="string", length=255, unique=true)
     */
    private $slug;

    /**
     * @var string
     * @ORM\Column(type="text", nullable=true)
     */
    private $description;

    /**
     * @ORM\Column(type="guid", nullable=true)
     */
    private $parentId;

    /**
     * @ORM\Column(type="integer")
     */
    private $treeId;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $treeParentId;



    /**
     * Categoria constructor.
     */
    public function __construct()
    {
        if (!$this->id) {
            $this->id = Uuid::uuid4();
        }
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return string
     */
    public function getSlug(): string
    {
        return $this->slug;
    }

    /**
     * @param string $slug
     */
    public function setSlug(string $slug)
    {
        $this->slug = $slug;
        return $this;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @param string $description
     */
    public function setDescription(string $description)
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getParentId()
    {
        return $this->parentId;
    }

    /**
     * @param mixed $parentId
     */
    public function setParentId($parentId)
    {
        $this->parentId = $parentId;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getTreeId()
    {
        return $this->treeId;
    }

    /**
     * @param mixed $treeId
     */
    public function setTreeId($treeId)
    {
        $this->treeId = $treeId;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getTreeParentId()
    {
        return $this->treeParentId;
    }

    /**
     * @param mixed $treeParentId
     */
    public function setTreeParentId($treeParentId)
    {
        $this->treeParentId = $treeParentId;
        return $this;
    }



}