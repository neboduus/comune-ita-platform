<?php

namespace AppBundle\Entity;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\UniqueConstraint;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use JMS\Serializer\Annotation as Serializer;
use Swagger\Annotations as SWG;
use Symfony\Component\Validator\Constraints as Assert;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Folder
 *
 * @ORM\Table(name="folder",
 *   uniqueConstraints={
 *        @UniqueConstraint(name="title_unique",
 *            columns={"owner_id", "title"})
 *    })
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class Folder
{
  /**
   * @ORM\Column(type="guid")
   * @ORM\Id
   * @SWG\Property(description="Folder id", type="string")
   */
  private $id;

  /**
   * @var string
   *
   * @ORM\Column(name="title", type="string", length=255, nullable=false)
   * @Assert\NotBlank(message="Questo campo è obbligatorio (title)")
   * @SWG\Property(description="Folder's title", type="string")
   */
  private $title;

  /**
   * @var string
   *
   * @ORM\Column(name="description", type="text", nullable=true)
   * @SWG\Property(description="Folder's description", type="text")
   */
  private $description;

  /**
   * @ORM\ManyToOne(targetEntity="AppBundle\Entity\CPSUser")
   * @ORM\JoinColumn(name="owner_id", referencedColumnName="id", nullable=false)
   * @Assert\NotBlank(message="Questo campo è obbligatorio (owner)")
   * @SWG\Property(description="Folder's owner id", type="string")
   * @Serializer\Exclude()
   */
  private $owner;

  /**
   * @ORM\OneToMany(targetEntity="AppBundle\Entity\Document", mappedBy="folder")
   * @Serializer\Exclude()
   * @SWG\Property(description="Folder's documents")
   */
  private $documents;

  /**
   * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Ente")
   * @ORM\JoinColumn(name="tenant_id", referencedColumnName="id", nullable=false)
   * @Assert\NotBlank(message="Questo campo è obbligatorio (tenant)")
   * @SWG\Property(description="Folder's tenant id", type="string")
   * @Serializer\Exclude()
   */
  private $tenant;

  /**
   * Lists of services' ids: Many Folders have Many Correlated Services.
   * @ORM\ManyToMany(targetEntity="AppBundle\Entity\Servizio")
   * @ORM\JoinTable(name="folders_services",
   *      joinColumns={@ORM\JoinColumn(name="folder_id", referencedColumnName="id")},
   *      inverseJoinColumns={@ORM\JoinColumn(name="service_id", referencedColumnName="id")}
   *      )
   * @SWG\Property(description="Folder's correlated services", type="array",  @SWG\Items(type="string"))
   * @Serializer\Exclude()
   */
  private $correlatedServices;

  /**
   * @ORM\Column(name="created_at", type="datetime")
   * @SWG\Property(description="Folder's creation date")
   */
  private $createdAt;

  /**
   * @ORM\Column(name="updated_at", type="datetime")
   * @SWG\Property(description="Folder's last modified date")
   */
  private $updatedAt;

  /**
   * Folder constructor.
   * @throws \Exception
   */
  public function __construct()
  {
    if (!$this->id) {
      $this->id = Uuid::uuid4();
      $this->correlatedServices = new ArrayCollection();
      $this->documents = new ArrayCollection();
    }
  }

  /**
   * get id
   *
   * @return UuidInterface
   */
  public function getId()
  {
    return $this->id;
  }

  /**
   * Set title.
   *
   * @param string $title
   *
   * @return Folder
   */
  public function setTitle($title)
  {
    $this->title = $title;

    return $this;
  }

  /**
   * Get title.
   *
   * @return string
   */
  public function getTitle()
  {
    return $this->title;
  }

  /**
   * Set description.
   *
   * @param string $description
   *
   * @return Folder
   */
  public function setDescription($description)
  {
    $this->description = $description;

    return $this;
  }

  /**
   * Get description.
   *
   * @return string
   */
  public function getDescription()
  {
    return $this->description;
  }

  /**
   * Get Owner
   *
   * @return User|null
   */
  public function getOwner(): ?User
  {
    return $this->owner;
  }

  /**
   * Get Owner
   *
   * @param User|null $owner
   * @return $this
   */
  public function setOwner(?User $owner): self
  {
    $this->owner = $owner;

    return $this;
  }

  /**
   * @Serializer\VirtualProperty(name="owner")
   * @Serializer\Type("string")
   * @Serializer\SerializedName("owner")
   *
   */
  public function getOwnerId(): string
  {
    return $this->owner->getId();
  }

  /**
   * Get Tenant
   *
   * @return User|null
   */
  public function getTenant(): ?Ente
  {
    return $this->tenant;
  }

  /**
   * Set Tenant
   *
   * @param Ente|null $tenant
   * @return $this
   */
  public function setTenant(?Ente $tenant): self
  {
    $this->tenant = $tenant;

    return $this;
  }

  /**
   * @Serializer\VirtualProperty(name="tenant")
   * @Serializer\Type("string")
   * @Serializer\SerializedName("tenant")
   *
   */
  public function getTenantId(): string
  {
    return $this->getTenant()->getId();
  }

  /**
   * @param Servizio[] $correlatedServices
   *
   * @return $this
   */
  public function serCorrelatedServices($correlatedServices)
  {
    $this->correlatedServices = $correlatedServices;

    return $this;
  }

  /**
   * @return Collection
   */
  public function getCorrelatedServices()
  {
    return $this->correlatedServices;
  }
  /**
   * @Serializer\VirtualProperty(name="correlatedServices")
   * @Serializer\Type("array<string>")
   * @Serializer\SerializedName("correlated_services")
   *
   */
  public function getServicesId(): array
  {
    $correlatedServices = [];
    foreach ($this->getCorrelatedServices() as $service)
    {
      $correlatedServices[] = $service->getId();
    }
    return $correlatedServices;
  }

  /**
   * @return Collection|Document[]
   */
  public function getDocuments(): Collection
  {
    return $this->documents;
  }

  /**
   * Get createdAt.
   *
   * @return \DateTime
   */
  public function getCreatedAt(): ?\DateTimeInterface
  {
    return $this->createdAt;
  }

  /**
   * Set createdAt
   *
   * @param \DateTimeInterface $updated_at
   *
   * @return $this
   */
  public function setCreatedAt(\DateTimeInterface $created_at): self
  {
    $this->createdAt = $created_at;

    return $this;
  }

  /**
   * Get updatedAt
   *
   * @return \DateTimeInterface|null
   */
  public function getUpdatedAt(): ?\DateTimeInterface
  {
    return $this->updatedAt;
  }

  /**
   * Set updatedAt
   *
   * @param \DateTimeInterface $updated_at
   *
   * @return $this
   */
  public function setUpdatedAt(\DateTimeInterface $updated_at): self
  {
    $this->updatedAt = $updated_at;

    return $this;
  }

  /**
   * @ORM\PrePersist
   * @ORM\PreUpdate
   */
  public function updatedTimestamps(): void
  {
    $dateTimeNow = new DateTime('now');

    $this->setUpdatedAt($dateTimeNow);

    if ($this->getCreatedAt() === null) {
      $this->setCreatedAt($dateTimeNow);
    }
  }

  /**
   * @return string
   */
  public function __toString()
  {
    return (string)$this->getTitle();
  }

}
