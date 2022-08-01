<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Gedmo\Translatable\Translatable;
use JMS\Serializer\Annotation as Serializer;
use JMS\Serializer\Annotation\Groups;
use Ramsey\Uuid\Uuid;
use Swagger\Annotations as SWG;
use Symfony\Component\Validator\Constraints as Assert;


/**
 * @ORM\Entity
 * @ORM\Table(name="geographic_area")
 */
class GeographicArea implements Translatable
{

  use TimestampableEntity;

  /**
   * @ORM\Column(type="guid")
   * @ORM\Id
   * @Serializer\Type("string")
   * @SWG\Property(description="Geographic Area id")
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
   * @SWG\Property(description="Geographic Area name")
   * @Groups({"read", "write"})
   */
  private $name;

  /**
   * @var string
   *
   * @Gedmo\Slug(fields={"name"})
   * @ORM\Column(type="string", length=255, unique=true)
   * @Serializer\Type("string")
   * @SWG\Property(description="Geographic Area slug")
   * @Groups({"read"})
   */
  private $slug;

  /**
   * @var string
   * @Gedmo\Translatable
   * @ORM\Column(type="text", nullable=true)
   * @Serializer\Type("string")
   * @SWG\Property(description="Geographic Area description")
   * @Groups({"read", "write"})
   */
  private $description;

  /**
   * @var string
   * @ORM\Column(type="json", options={"jsonb":true}, nullable=true)
   * @Serializer\Type("string")
   * @Groups({"read", "write"})
   */
  private $geofence;

  /**
   * @ORM\ManyToMany(targetEntity="App\Entity\Servizio", mappedBy="geographicAreas")
   * @var ArrayCollection
   * @Serializer\Exclude()
   */
  private $services;

  /**
   * @ORM\ManyToMany(targetEntity="App\Entity\ServiceGroup", mappedBy="geographicAreas")
   * @var ArrayCollection
   * @Serializer\Exclude()
   */
  private $servicesGroup;

  /**
   * @Serializer\Exclude()
   * @Gedmo\Locale
   * Used locale to override Translation listener`s locale
   * this is not a mapped field of entity metadata, just a simple property
   */
  private $locale;


  /**
   * Categoria constructor.
   */
  public function __construct()
  {
    if (!$this->id) {
      $this->id = Uuid::uuid4();
    }
    $this->services = new ArrayCollection();
    $this->servicesGroup = new ArrayCollection();
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
   * @return string
   */
  public function getGeofence(): ?string
  {
    return $this->geofence;
  }

  /**
   * @param string $geofence
   */
  public function setGeofence(?string $geofence): void
  {
    $this->geofence = $geofence;
  }

  /**
   * @return ArrayCollection
   */
  public function getServices(): ArrayCollection
  {
    return $this->services;
  }

  /**
   * @param ArrayCollection $services
   */
  public function setServices(ArrayCollection $services): void
  {
    $this->services = $services;
  }

  /**
   * @return ArrayCollection
   */
  public function getServicesGroup(): ArrayCollection
  {
    return $this->servicesGroup;
  }

  /**
   * @param ArrayCollection $servicesGroup
   */
  public function setServicesGroup(ArrayCollection $servicesGroup): void
  {
    $this->servicesGroup = $servicesGroup;
  }

  public function setTranslatableLocale($locale)
  {
    $this->locale = $locale;
  }

}
