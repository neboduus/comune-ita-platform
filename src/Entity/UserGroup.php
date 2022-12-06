<?php

namespace App\Entity;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Gedmo\Translatable\Translatable;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use OpenApi\Annotations as OA;
use JMS\Serializer\Annotation\Groups;
use Gedmo\Mapping\Annotation as Gedmo;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity
 * @ORM\Table(name="user_group")
 */
class UserGroup implements Translatable
{

  /**
   * Hook timestampable behavior
   * updates createdAt, updatedAt fields
   */
  use TimestampableEntity;

  /**
   * @ORM\Column(type="guid")
   * @ORM\Id
   * @Serializer\Type("string")
   * @OA\Property(description="UserGroup id")
   * @Groups({"read"})
   */
  protected $id;

  /**
   * @var string
   * @Gedmo\Translatable
   * @ORM\Column(type="string", length=255)
   * @Serializer\Type("string")
   * @Assert\NotBlank(message="user_group.name.not_blank")
   * @Assert\Length(max="255")
   * @OA\Property(description="UserGroup name")
   * @Groups({"read", "write"})
   */
  private $name;

  /**
   * @ORM\ManyToOne(targetEntity="App\Entity\Categoria", inversedBy="userGroups")
   * @Serializer\Exclude()
   */
  private $topic;

  /**
   * @var string
   * @Gedmo\Translatable
   * @ORM\Column(type="string", length=255, nullable=true)
   * @Assert\Length(max="255")
   * @Serializer\Type("string")
   * @OA\Property(description="UserGroup short description")
   * @Groups({"read", "write"})
   */
  private $shortDescription;

  /**
   * @var string
   * @Gedmo\Translatable
   * @ORM\Column(type="text", nullable=true)
   * @Serializer\Type("string")
   * @OA\Property(description="UserGroup main function")
   * @Groups({"read", "write"})
   */
  private $mainFunction;

  /**
   * @var string
   * @Gedmo\Translatable
   * @ORM\Column(type="text", nullable=true)
   * @Serializer\Type("string")
   * @OA\Property(description="UserGroup more info")
   * @Groups({"read", "write"})
   */
  private $moreInfo;

  /**
   * @ORM\ManyToOne(targetEntity="App\Entity\OperatoreUser", inversedBy="userGroupsManager")
   * @Serializer\Exclude()
   */
  private $manager;

  /**
   * Many services can be in many groups
   * @ORM\ManyToMany(targetEntity="App\Entity\Servizio", inversedBy="userGroups")
   * @var ArrayCollection
   * @Serializer\Exclude()
   */
  private $services;

  /**
   * Many users can have many groups
   * @ORM\ManyToMany(targetEntity="App\Entity\OperatoreUser", inversedBy="userGroups")
   * @var ArrayCollection
   * @Serializer\Exclude()
   */
  private $users;

  /**
   * @var ContactPoint
   * @ORM\ManyToOne(targetEntity=ContactPoint::class, cascade={"persist", "remove"})
   * @OA\Property(property="core_contact_point", description="User Group's Core Contact Point")
   * @Groups({"read", "write"})
   */
  private $coreContactPoint;

  /**
   * @var DateTime
   * @Gedmo\Timestampable(on="create")
   * @ORM\Column(type="datetime")
   * @Groups({"read"})
   */
  protected $createdAt;

  /**
   * @var DateTime
   * @Gedmo\Timestampable(on="update")
   * @ORM\Column(type="datetime")
   * @Groups({"read"})
   */
  protected $updatedAt;

  /**
   * @Serializer\Exclude()
   * @Gedmo\Locale
   * Used locale to override Translation listener`s locale
   * this is not a mapped field of entity metadata, just a simple property
   */
  private $locale;

  public function __construct()
  {
    if (!$this->id) {
      $this->id = Uuid::uuid4();
    }

    $this->users = new ArrayCollection();
    $this->services = new ArrayCollection();
  }

  /**
   * @return mixed
   */
  public function getId()
  {
    return $this->id;
  }

  /**
   * @param mixed $id
   */
  public function setId($id): void
  {
    $this->id = $id;
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
  public function setName(string $name): void
  {
    $this->name = $name;
  }

  /**
   * @return Categoria|null
   */
  public function getTopic(): ?Categoria
  {
    return $this->topic;
  }

  /**
   * @param Categoria|null $topic
   * @return void
   */
  public function setTopic(?Categoria $topic): void
  {
    $this->topic = $topic;
  }

  /**
   * @Serializer\VirtualProperty()
   * @Serializer\Type("string")
   * @Serializer\SerializedName("topic_id")
   * @OA\Property(description="UserGroup topic id (uuid)")
   * @Groups({"read", "write"})
   */
  public function getTopicId()
  {
    if ($this->topic instanceof Categoria) {
      return $this->topic->getId();
    }
    return null;
  }

  /**
   * @return string
   */
  public function getShortDescription(): ?string
  {
    return $this->shortDescription;
  }

  /**
   * @param string|null $shortDescription
   */
  public function setShortDescription(?string $shortDescription): void
  {
    $this->shortDescription = $shortDescription;
  }

  /**
   * @return string
   */
  public function getMainFunction(): ?string
  {
    return $this->mainFunction;
  }

  /**
   * @param string|null $mainFunction
   */
  public function setMainFunction(?string $mainFunction): void
  {
    $this->mainFunction = $mainFunction;
  }

  /**
   * @return mixed
   */
  public function getManager(): ?OperatoreUser
  {
    return $this->manager;
  }

  /**
   * @param mixed $manager
   */
  public function setManager(?OperatoreUser $manager): void
  {
    $this->manager = $manager;
  }

  /**
   * @Serializer\VirtualProperty()
   * @Serializer\Type("string")
   * @Serializer\SerializedName("manager_id")
   * @OA\Property(description="UserGroup manager id (uuid)")
   * @Groups({"read", "write"})
   */
  public function getManagerId()
  {
    if ($this->manager instanceof OperatoreUser) {
      return $this->manager->getId();
    }
    return null;
  }

  /**
   * @return string
   */
  public function getMoreInfo(): ?string
  {
    return $this->moreInfo;
  }

  /**
   * @param string|null $moreInfo
   */
  public function setMoreInfo(?string $moreInfo): void
  {
    $this->moreInfo = $moreInfo;
  }

  /**
   * @return Collection<int, OperatoreUser>
   */
  public function getUsers(): Collection
  {
    return $this->users;
  }

  /**
   * @param Collection|null $users
   */
  public function setUsers(?Collection $users): void
  {
    $this->users = $users;
  }

  /**
   * @Serializer\VirtualProperty()
   * @Serializer\Type("integer")
   * @Serializer\SerializedName("users_count")
   * @OA\Property(description="Users count")
   * @Groups({"read"})
   */
  public function getUsersCount(): int
  {
    return $this->users->count();
  }

  public function addUser(OperatoreUser $user): self
  {
    if (!$this->users->contains($user)) {
      $this->users[] = $user;
    }

    return $this;
  }

  public function removeUser(OperatoreUser $user): self
  {
    $this->users->removeElement($user);

    return $this;
  }

  /**
   * @return Collection<int, Servizio>
   */
  public function getServices(): Collection
  {
    return $this->services;
  }

  /**
   * @param Collection|null $services
   */
  public function setServices(?Collection $services): void
  {
    $this->services = $services;
  }

  /**
   * @Serializer\VirtualProperty()
   * @Serializer\Type("integer")
   * @Serializer\SerializedName("services_count")
   * @OA\Property(description="Services count")
   * @Groups({"read"})
   */
  public function getServicesCount(): int
  {
    return $this->services->count();
  }

  public function addService(Servizio $service): self
  {
    if (!$this->services->contains($service)) {
      $this->services[] = $service;
    }

    return $this;
  }

  public function removeService(Servizio $service): self
  {
    $this->services->removeElement($service);

    return $this;
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

  public function setTranslatableLocale($locale)
  {
    $this->locale = $locale;
  }
}
