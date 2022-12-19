<?php

namespace App\Entity;

use App\Model\PostalAddress;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Gedmo\Translatable\Translatable;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use App\Repository\PlaceRepository;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use OpenApi\Annotations as OA;
use JMS\Serializer\Annotation\Groups;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;
use Nelmio\ApiDocBundle\Annotation\Model;

/**
 * @ORM\Entity(repositoryClass=PlaceRepository::class)
 */
class Place implements Translatable
{
  /**
   * @ORM\Column(type="guid")
   * @ORM\Id
   * @Serializer\Type("string")
   * @OA\Property(description="Place id")
   * @Groups({"read"})
   */
  private $id;

  /**
   * @var string
   * @Gedmo\Translatable
   * @ORM\Column(type="string", length=255)
   * @Serializer\Type("string")
   * @Assert\NotBlank(message="user_group.name.not_blank")
   * @Assert\NotNull()
   * @OA\Property(description="Place name")
   * @Groups({"read", "write"})
   */
  private $name;

  /**
   * @var string
   * @Gedmo\Translatable
   * @ORM\Column(type="string", length=255, nullable=true)
   * @Serializer\Type("string")
   * @OA\Property(description="Place short description")
   * @Groups({"read", "write"})
   */
  private $otherName;

  /**
   * @ORM\ManyToOne(targetEntity=Categoria::class, inversedBy="places")
   * @Serializer\Exclude()
   */
  private $topic;

  /**
   * @var string
   * @Gedmo\Translatable
   * @ORM\Column(type="string", length=255, nullable=true)
   * @Serializer\Type("string")
   * @OA\Property(description="Place short description")
   * @Groups({"read", "write"})
   */
  private $shortDescription;

  /**
   * @var string
   * @Gedmo\Translatable
   * @ORM\Column(type="text", nullable=true)
   * @Serializer\Type("string")
   * @OA\Property(description="Place description")
   * @Groups({"read", "write"})
   */
  private $description;

  /**
   * @var PostalAddress
   * @ORM\Column(type="json", nullable=true)
   * @OA\Property(property="address", description="Address of the place", type="object", ref=@Model(type=PostalAddress::class))
   * @Assert\Valid()
   * @Groups({"read", "write"})
   */
  private $address;

  /**
   * @var string
   * @Gedmo\Translatable
   * @ORM\Column(type="string", nullable=true)
   * @Serializer\Type("string")
   * @OA\Property(description="Place latitude")
   * @Groups({"read", "write"})
   */
  private $latitude;

  /**
   * @var string
   * @Gedmo\Translatable
   * @ORM\Column(type="string", nullable=true)
   * @Serializer\Type("string")
   * @OA\Property(description="Place longitude")
   * @Groups({"read", "write"})
   */
  private $longitude;

  /**
   * @var string
   * @Gedmo\Translatable
   * @ORM\Column(type="text", nullable=true)
   * @Serializer\Type("string")
   * @OA\Property(description="Place more info")
   * @Groups({"read", "write"})
   */
  private $moreInfo;

  /**
   * @var string
   * @Gedmo\Translatable
   * @ORM\Column(type="string", nullable=true)
   * @Serializer\Type("string")
   * @OA\Property(description="Place longitude")
   * @Groups({"read", "write"})
   */
  private $identifier;

  /**
   * @ORM\ManyToMany(targetEntity=GeographicArea::class, inversedBy="places")
   * @Serializer\Exclude()
   */
  private $geographicAreas;

  /**
   * @var ContactPoint
   * @ORM\ManyToOne(targetEntity=ContactPoint::class, cascade={"persist", "remove"})
   * @OA\Property(property="core_contact_point", description="Place's Core Contact Point")
   * @Assert\Valid()
   * @Groups({"read", "write"})
   */
  private $coreContactPoint;

  public function __construct()
  {
    if (!$this->id) {
      $this->id = Uuid::uuid4();
    }
    $this->geographicAreas = new ArrayCollection();
  }

  /**
   * @return mixed
   */
  public function getId()
  {
    return $this->id;
  }

  public function getName(): ?string
  {
    return $this->name;
  }

  public function setName(string $name): self
  {
    $this->name = $name;

    return $this;
  }

  public function getOtherName(): ?string
  {
    return $this->otherName;
  }

  public function setOtherName(?string $otherName): self
  {
    $this->otherName = $otherName;

    return $this;
  }

  public function getTopic(): ?Categoria
  {
    return $this->topic;
  }

  public function setTopic(?Categoria $topic): self
  {
    $this->topic = $topic;

    return $this;
  }

  /**
   * @Serializer\VirtualProperty()
   * @Serializer\Type("string")
   * @Serializer\SerializedName("topic_id")
   * @OA\Property(description="Place topic id (uuid)", type="string", format="uuid")
   * @Groups({"read", "write"})
   */
  public function getTopicId()
  {
    if ($this->topic instanceof Categoria) {
      return $this->topic->getId();
    }
    return null;
  }

  public function getShortDescription(): ?string
  {
    return $this->shortDescription;
  }

  public function setShortDescription(?string $shortDescription): self
  {
    $this->shortDescription = $shortDescription;

    return $this;
  }

  public function getDescription(): ?string
  {
    return $this->description;
  }

  public function setDescription(?string $description): self
  {
    $this->description = $description;

    return $this;
  }

  public function getAddress()
  {
    return $this->address;
  }

  public function setAddress($address): self
  {
    $this->address = $address;

    return $this;
  }

  public function getLatitude(): ?string
  {
    return $this->latitude;
  }

  public function setLatitude(?string $latitude): self
  {
    $this->latitude = $latitude;

    return $this;
  }

  public function getLongitude(): ?string
  {
    return $this->longitude;
  }

  public function setLongitude(?string $longitude): self
  {
    $this->longitude = $longitude;

    return $this;
  }

  public function getMoreInfo(): ?string
  {
    return $this->moreInfo;
  }

  public function setMoreInfo(?string $moreInfo): self
  {
    $this->moreInfo = $moreInfo;

    return $this;
  }

  public function getIdentifier(): ?string
  {
    return $this->identifier;
  }

  public function setIdentifier(?string $identifier): self
  {
    $this->identifier = $identifier;

    return $this;
  }

  /**
   * @return Collection<int, GeographicArea>
   */
  public function getGeographicAreas(): Collection
  {
    return $this->geographicAreas;
  }

  public function addGeographicArea(GeographicArea $geographicArea): self
  {
    if (!$this->geographicAreas->contains($geographicArea)) {
      $this->geographicAreas[] = $geographicArea;
    }

    return $this;
  }

  public function removeGeographicArea(GeographicArea $geographicArea): self
  {
    $this->geographicAreas->removeElement($geographicArea);

    return $this;
  }


  /**
   * @Serializer\VirtualProperty()
   * @Serializer\Type("array<string>")
   * @Serializer\SerializedName("geographic_area_ids")
   * @OA\Property(description="Place geographic area ids (uuid)", type="array", @OA\Items(type="string", format="uuid"))
   * @Groups({"read", "write"})
   */
  public function getGeographicAreaIds(): ?array
  {
    $geographicAreas = [];
    foreach ($this->getGeographicAreas() as $geographicArea)
    {
      if ($geographicArea instanceof GeographicArea) {
        $geographicAreas[] = $geographicArea->getId();
      }
    }
    return $geographicAreas;
  }

  public function getCoreContactPoint(): ?ContactPoint
  {
    return $this->coreContactPoint;
  }

  public function setCoreContactPoint(?ContactPoint $coreContactPoint): self
  {
    $this->coreContactPoint = $coreContactPoint;

    return $this;
  }
}
