<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use JMS\Serializer\Annotation as Serializer;
use JMS\Serializer\Annotation\Groups;
use Ramsey\Uuid\Uuid;
use Swagger\Annotations as SWG;


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
   * @Serializer\Type("string")
   * @SWG\Property(description="Category id")
   * @Groups({"read"})
   */
  protected $id;

  /**
   * @var string
   * @ORM\Column(type="string", length=255)
   * @Serializer\Type("string")
   * @SWG\Property(description="Category name")
   * @Groups({"read", "write"})
   */
  private $name;

  /**
   * @var string
   *
   * @Gedmo\Slug(fields={"name"})
   * @ORM\Column(type="string", length=255, unique=true)
   * @Serializer\Type("string")
   * @SWG\Property(description="Category slug")
   * @Groups({"read"})
   */
  private $slug;

  /**
   * @var string
   * @ORM\Column(type="text", nullable=true)
   * @Serializer\Type("string")
   * @SWG\Property(description="Category description")
   * @Groups({"read", "write"})
   */
  private $description;

  /**
   * @ORM\OneToMany(targetEntity="Categoria", mappedBy="parent", fetch="EXTRA_LAZY")
   * @Serializer\Exclude()
   */
  private $children;

  /**
   * @ORM\ManyToOne(targetEntity="Categoria", inversedBy="children")
   * @ORM\JoinColumn(name="parent_id", referencedColumnName="id")
   * @Serializer\Exclude
   */
  private $parent;


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
  public function getName(): ?string
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
  public function getSlug(): ?string
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
  public function getDescription(): ?string
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
  public function getChildren()
  {
    return $this->children;
  }

  /**
   * @param mixed $children
   */
  public function setChildren($children): void
  {
    $this->children = $children;
  }

  /**
   * @return mixed
   */
  public function getParent()
  {
    return $this->parent;
  }

  /**
   * @Serializer\VirtualProperty()
   * @Serializer\Type("string")
   * @Serializer\SerializedName("parent_id")
   * @SWG\Property(description="Parent category id")
   * @Groups({"read", "write"})
   */
  public function getParentId()
  {
    if ($this->parent) {
      return $this->parent->getId();
    }
    return null;
  }

  /**
   * @param mixed $parent
   */
  public function setParent($parent): void
  {
    $this->parent = $parent;
  }
}
