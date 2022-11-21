<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
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
 * @ORM\HasLifecycleCallbacks
 */
class UserGroup implements Translatable
{

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
   * @Assert\NotNull()
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
   * @return mixed
   */
  public function getTopic()
  {
    return $this->topic;
  }

  /**
   * @param mixed $topic
   */
  public function setTopic($topic): void
  {
    $this->topic = $topic;
  }

  /**
   * @return string
   */
  public function getShortDescription(): string
  {
    return $this->shortDescription;
  }

  /**
   * @param string $shortDescription
   */
  public function setShortDescription(string $shortDescription): void
  {
    $this->shortDescription = $shortDescription;
  }

  /**
   * @return string
   */
  public function getMainFunction(): string
  {
    return $this->mainFunction;
  }

  /**
   * @param string $mainFunction
   */
  public function setMainFunction(string $mainFunction): void
  {
    $this->mainFunction = $mainFunction;
  }

  /**
   * @return mixed
   */
  public function getManager()
  {
    return $this->manager;
  }

  /**
   * @param mixed $manager
   */
  public function setManager($manager): void
  {
    $this->manager = $manager;
  }

  /**
   * @return string
   */
  public function getMoreInfo(): string
  {
    return $this->moreInfo;
  }

  /**
   * @param string $moreInfo
   */
  public function setMoreInfo(string $moreInfo): void
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
}
