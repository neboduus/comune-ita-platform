<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Translatable\Translatable;
use JMS\Serializer\Annotation as Serializer;
use JMS\Serializer\Annotation\Groups;
use Ramsey\Uuid\Uuid;
use OpenApi\Annotations as OA;
use Symfony\Component\Validator\Constraints as Assert;


/**
 * @ORM\Entity
 * @ORM\Table(name="categoria")
 * @ORM\HasLifecycleCallbacks
 */
class Categoria implements Translatable
{
  /**
   * @ORM\Column(type="guid")
   * @ORM\Id
   * @Serializer\Type("string")
   * @OA\Property(description="Category id")
   * @Groups({"read"})
   */
  protected $id;

  /**
   * @var string
   * @Gedmo\Translatable
   * @ORM\Column(type="string", length=255)
   * @Serializer\Type("string")
   * @Assert\NotBlank(message="name")
   * @Assert\NotNull()
   * @OA\Property(description="Category name")
   * @Groups({"read", "write"})
   */
  private $name;

  /**
   * @var string
   *
   * @Gedmo\Slug(fields={"name"})
   * @ORM\Column(type="string", length=255, unique=true)
   * @Serializer\Type("string")
   * @OA\Property(description="Category slug")
   * @Groups({"read"})
   */
  private $slug;

  /**
   * @var string
   * @Gedmo\Translatable
   * @ORM\Column(type="text", nullable=true)
   * @Serializer\Type("string")
   * @OA\Property(description="Category description")
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
   * @ORM\OneToMany(targetEntity="App\Entity\Servizio", mappedBy="topics")
   * @var ArrayCollection
   * @Serializer\Exclude()
   */
  private $services;


  /**
   * @ORM\OneToMany(targetEntity="App\Entity\ServiceGroup", mappedBy="topics")
   * @var ArrayCollection
   * @Serializer\Exclude()
   */
  private $servicesGroup;


  /**
   * Categoria constructor.
   */
  public function __construct()
  {
    if (!$this->id) {
      $this->id = Uuid::uuid4();
    }
    $this->children = new ArrayCollection();
    $this->services = new ArrayCollection();
    $this->servicesGroup = new ArrayCollection();
  }

  public function __toString()
  {
    return $this->getId();
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
  public function setDescription(?string $description)
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
   * @OA\Property(description="Parent category id")
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

  /**
   * @return ArrayCollection
   */
  public function getServices()
  {
    return $this->services;
  }

  /**
   * @param ArrayCollection $services
   */
  public function setServices($services): void
  {
    $this->services = $services;
  }

  /**
   * @return ArrayCollection
   */
  public function getServicesGroup()
  {
    return $this->servicesGroup;
  }

  /**
   * @param ArrayCollection $servicesGroup
   */
  public function setServicesGroup($servicesGroup): void
  {
    $this->servicesGroup = $servicesGroup;
  }

  public function getVisibleService()
  {
    $services = new ArrayCollection();
    /** @var Servizio $service */
    foreach ($this->services as $service) {
      if (in_array($service->getStatus(), Servizio::PUBLIC_STATUSES)) {
        $services []= $service;
      }
    }
    return $services;
  }

  public function getVisibleServicesGroup()
  {
    $services = new ArrayCollection();
    /** @var ServiceGroup $serviceGroup */
    foreach ($this->servicesGroup as $serviceGroup) {
      /** @var Servizio $service */
      foreach ($serviceGroup->getServices() as $service) {
        if (in_array($service->getStatus(), Servizio::PUBLIC_STATUSES)) {
          $services []= $serviceGroup;
          break;
        }
      }
    }
    return $services;
  }

  /**
   * @return bool
   */
  public function hasRelations(): bool
  {
    return $this->getChildren()->count() > 0 || $this->getServices()->count() > 0 || $this->getServicesGroup()->count() > 0;
  }

  /**
   * @return bool
   */
  public function hasVisibleRelations(): bool
  {
    return $this->getChildren()->count() > 0 || $this->getVisibleService()->count() > 0 || $this->getVisibleServicesGroup()->count() > 0;
  }
}
