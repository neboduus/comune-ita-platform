<?php

namespace App\Entity;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Mapping as ORM;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\UniqueConstraint;
use JMS\Serializer\Annotation\Groups;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use JMS\Serializer\Annotation as Serializer;
use Swagger\Annotations as SWG;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Document
 *
 * @ORM\Table(name="document", uniqueConstraints={
 *        @UniqueConstraint(name="title_unique_for_folder",
 *            columns={"owner_id", "title", "folder_id"})
 *    }))
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class Document
{
  const RECIPIENT_TENANT = 'tenant';
  const RECIPIENT_USER = 'user';
  /**
   * @ORM\Column(type="guid")
   * @ORM\Id
   * @SWG\Property(description="Document's uuid", type="string", format="uuid")
   * @Groups({"read"})
   */
  private $id;

  /**
 * @ORM\ManyToOne(targetEntity="App\Entity\CPSUser")
 * @ORM\JoinColumn(name="owner_id", referencedColumnName="id", nullable=false)
 * @Assert\NotBlank(message="Questo campo è obbligatorio (owner)")
 * @SWG\Property(description="Document's owner id", type="string")
 * @Serializer\Exclude()
 */
  private $owner;

  /**
   * @ORM\ManyToOne(targetEntity="App\Entity\Folder", inversedBy="documents")
   * @ORM\JoinColumn(name="folder_id", referencedColumnName="id", nullable=false)
   * @Assert\NotBlank(message="Questo campo è obbligatorio (folder)")
   * @SWG\Property(description="Document's folder id", type="string")
   * @Serializer\Exclude()
   */
  private $folder;

  /**
   * @var string
   *
   * @ORM\Column(name="recipient_type", type="string", length=255)
   * @Assert\NotBlank(message="Seleziona un'opzione. RecipientType è un parametro obbligatorio")
   * @SWG\Property(description="Document's recipient type. Specifies whether the document has been uploaded tby the citizen or generated by the municipality. Accepts values: tenant, user", type="string", example="user")
   * @Groups({"read"})
   */
  private $recipientType;

  /**
   * @var int
   *
   * @ORM\Column(name="version", type="integer")
   * @SWG\Property(description="Document's version, updated on document changes", type="integer")
   * @Groups({"read"})
   */
  private $version;

  /**
   * @var string|null
   * @ORM\Column(name="md5", type="string", length=255, nullable=true)
   * @SWG\Property(description="Document's md5 (if provided it's used to check the validity of the document, it will be calculated on the provided document otherwise)", type="string")
   * @Groups({"read", "write"})
   */
  private $md5;

  /**
   * @var string
   * @Assert\NotBlank(message="Questo campo è obbligatorio (original Filename)")
   * @ORM\Column(name="original_filename", type="string", length=255, nullable=false)
   * @SWG\Property(description="Document's original file name", type="string")
   * @Groups({"read", "write"})
   */
  private $originalFilename;

  /**
   * @var string|null
   *
   * @ORM\Column(name="mimeType", type="string", length=255, nullable=true)
   * @SWG\Property(description="Document's mime type", type="string")
   * @Groups({"read", "write"})
   */
  private $mimeType;

  /**
   * @var string
   * @ORM\Column(name="address", type="string", length=255, nullable=true)
   * @SWG\Property(description="Document's url address. If both file and address are provided, file is kept", type="string", example="https://www.example.com/file")
   * @Groups({"read", "write"})
   */
  private $address;

  /**
   * @var string
   * @Assert\NotBlank(message="Questo campo è obbligatorio (download_link)")
   * @ORM\Column(name="download_link", type="string", length=255, nullable=false)
   * @SWG\Property(description="Document's download link. ", type="string", example="https://www.example.com/download-file")
   * @Groups({"read"})
   */
  private $downloadLink;

  /**
   * @ORM\ManyToOne(targetEntity="App\Entity\Ente")
   * @ORM\JoinColumn(name="tenant_id", referencedColumnName="id", nullable=false)
   * @Assert\NotBlank(message="Questo campo è obbligatorio (tenant)")
   * @SWG\Property(description="Document's tenant id", type="string")
   * @Serializer\Exclude()
   */
  private $tenant;

  /**
   * @var string
   * @Assert\NotBlank(message="Questo campo è obbligatorio (title)")
   * @ORM\Column(name="title", type="string", length=255, nullable=false)
   * @SWG\Property(description="Document's title", type="string")
   * @Groups({"read", "write"})
   */
  private $title;

  /**
   * Many Documents have Many Topics.
   * @ORM\ManyToMany(targetEntity="App\Entity\Categoria")
   * @ORM\JoinTable(name="documents_topics",
   *      joinColumns={@ORM\JoinColumn(name="document_id", referencedColumnName="id")},
   *      inverseJoinColumns={@ORM\JoinColumn(name="categoria_id", referencedColumnName="id")}
   *      )
   * @SWG\Property(description="Document's topics, relation to Category Entity", type="array", @SWG\Items(type="string"))
   * @Serializer\Exclude()
   */
  private $topics;

  /**
   * @var string|null
   *
   * @ORM\Column(name="description", type="text", nullable=true)
   * SWG\Property(description="Document's description", type="string")
   * @Groups({"read", "write"})
   */
  private $description;

  /**
   * @var string
   *
   * @ORM\Column(name="readers_allowed", type="json", nullable=true)
   * @SWG\Property(description="Document's allowed readers (list of fiscal codes of users that are allowed to read the document)", type="array", @SWG\Items(type="string"))
   * @Groups({"read", "write"})
   */
  private $readersAllowed;

  /**
   * @var \DateTime|null
   *
   * @ORM\Column(name="last_read_at", type="datetime", nullable=true)
   * @SWG\Property(description="Document's last read date")
   * @Serializer\SkipWhenEmpty()
   * @Groups({"read"})
   */
  private $lastReadAt;

  /**
   * @var integer
   *
   * @ORM\Column(name="downloads_counter", type="integer", nullable=true)
   * @SWG\Property(description="Document's downloads counter")
   * @Groups({"read"})
   */
  private $downloadsCounter;

  /**
   * @var \DateTime|null
   *
   * @ORM\Column(name="validity_begin", type="datetime", nullable=true)
   * @Assert\Expression(
   *     "this.getValidityEnd() or (value == null and this.getValidityEnd() == null)",
   *     message="If validity begin is defined, validity end should be not null"
   * )
   * @SWG\Property(description="Document's validity begin date, after this date the document will have legal value")
   * @Groups({"read", "write"})
   */
  private $validityBegin;

  /**
   * @var \DateTime|null
   *
   * @ORM\Column(name="validity_end", type="datetime", nullable=true)
   * @Assert\Expression(
   *     "this.getValidityBegin() or (value == null and this.getValidityBegin() == null)",
   *     message="If validity end is defined, validity begin should be not null"
   * )
   * @Assert\LessThanOrEqual(propertyPath="expireAt", message="validityEnd must be lower than expire Date")
   * @SWG\Property(description="Document's validity end date, after this date the document will have no legal value")
   * @Groups({"read", "write"})
   */
  private $validityEnd;

  /**
   * @var \DateTime|null
   *
   * @ORM\Column(name="expire_at", type="datetime", nullable=true)
   * @Assert\LessThanOrEqual("+10 years", message="Maximum availability interval is 10 years")
   * @Assert\GreaterThan("today", message="Expire date must be greater than current day")
   * @SWG\Property(description="Document's expire date, after this date the document will not be available anymore")
   * @Groups({"read", "write"})
   */
  private $expireAt;

  /**
   * @var \DateTime|null
   *
   * @ORM\Column(name="due_date", type="datetime", nullable=true)
   * @SWG\Property(description="Document's due date")
   * @Groups({"read", "write"})
   */
  private $dueDate;

  /**
   * Many Documents have Many Correlated Services.
   * @ORM\ManyToMany(targetEntity="App\Entity\Servizio")
   * @ORM\JoinTable(name="document_services",
   *      joinColumns={@ORM\JoinColumn(name="document_id", referencedColumnName="id")},
   *      inverseJoinColumns={@ORM\JoinColumn(name="service_id", referencedColumnName="id")}
   *      )
   * @SWG\Property(description="Document's correlated services", type="array", @SWG\Items(type="string"))
   * @Serializer\Exclude()
   */
  private $correlatedServices;

  /**
   * * @var \DateTime
   *
   * @ORM\Column(name="created_at", type="datetime")
   * @SWG\Property(description="Document's creation date")
   * @Groups({"read"})
   */
  private $createdAt;

  /**
   * @var \DateTime
   *
   * @ORM\Column(name="updated_at", type="datetime")
   * @SWG\Property(description="Document's last modified date")
   * @Groups({"read"})
   */
  private $updatedAt;

  /**
   * @var bool
   * @ORM\Column(type="boolean", nullable=false, options={"default":"false"})
   * @SWG\Property(description="If the document should be saved in the filesystem")
   * @Groups({"read", "write"})
   */
  private $store;

  /**
   * Document constructor.
   * @throws \Exception
   */
  public function __construct()
  {
    if (!$this->id) {
      $this->id = Uuid::uuid4();
      $this->version = 1;
      $this->downloadsCounter = 0;
      $this->correlatedServices = new ArrayCollection();
      $this->topics = new ArrayCollection();
      $this->store = false;
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
 * Get Owner
 *
 * @return User|null
 */
  public function getOwner(): ?User
  {
    return $this->owner;
  }

  /**
   * Set Owner
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
   * @SWG\Property(description="Document owner's identifier", type="string", format="uuid")
   * @Serializer\SerializedName("owner")
   * @Groups({"read", "write"})
   *
   */
  public function getOwnerId(): string
  {
    return $this->owner->getId();
  }

  /**
   * Get store
   *
   * @return bool
   */
  public function isStore(): bool
  {
    return $this->store;
  }

  /**
   * Set store
   *
   * @param bool $store
   * @return $this
   */
  public function setStore(bool $store): self
  {
    $this->store = $store;
    return $this;
  }


  /**
   * @Serializer\VirtualProperty(name="file")
   * @Serializer\Type("string")
   * @SWG\Property(description="Base64 file to be imported", type="string")
   * @Serializer\SerializedName("file")
   * @Groups({"write"})
   *
   */
  public function getFile(): string
  {
    return "";
  }

  /**
   * Get Folder
   *
   * @return Folder|null
   */
  public function getFolder(): ?Folder
  {
    return $this->folder;
  }

  /**
   * Set Folder
   *
   * @param Folder|null $folder
   * @return $this
   */
  public function setFolder(?Folder $folder): self
  {
    $this->folder = $folder;

    return $this;
  }

  /**
   * @Serializer\VirtualProperty(name="folder")
   * @Serializer\Type("string")
   * @Serializer\SerializedName("folder")
   * @SWG\Property(description="Document folder's identifier", type="string", format="uuid")
   * @Groups({"read", "write"})
   *
   */
  public function getFolderId(): string
  {
    return $this->getFolder()->getId();
  }

  /**
   * Set recipientType.
   *
   * @param string $recipientType
   *
   * @return Document
   */
  public function setRecipientType($recipientType)
  {
    $this->recipientType = $recipientType;

    return $this;
  }

  /**
   * Get recipientType.
   *
   * @return string
   */
  public function getRecipientType()
  {
    return $this->recipientType;
  }

  /**
   * Set version.
   *
   * @param int $version
   *
   * @return Document
   */
  public function setVersion($version)
  {
    $this->version = $version;

    return $this;
  }

  /**
   * Get version.
   *
   * @return int
   */
  public function getVersion()
  {
    return $this->version;
  }

  /**
   * Set md5.
   *
   * @param string|null $md5
   *
   * @return Document
   */
  public function setMd5($md5 = null)
  {
    $this->md5 = $md5;

    return $this;
  }

  /**
   * Get md5.
   *
   * @return string|null
   */
  public function getMd5()
  {
    return $this->md5;
  }

  /**
   * Set mimeType.
   *
   * @param string|null $mimeType
   *
   * @return Document
   */
  public function setMimeType($mimeType = null)
  {
    $this->mimeType = $mimeType;

    return $this;
  }

  /**
   * Get mimeType.
   *
   * @return string|null
   */
  public function getMimeType()
  {
    return $this->mimeType;
  }

  /**
   * Set originalFilename.
   *
   * @param string|null $originalFilename
   *
   * @return Document
   */
  public function setOriginalFilename($originalFilename = null)
  {
    $this->originalFilename = $originalFilename;

    return $this;
  }

  /**
   * Get originalFilename.
   *
   * @return string|null
   */
  public function getOriginalFilename()
  {
    return $this->originalFilename;
  }

  /**
   * Set address.
   *
   * @param string $address
   *
   * @return Document
   */
  public function setAddress($address)
  {
    $this->address = $address;

    return $this;
  }

  /**
   * Get address.
   *
   * @return string
   */
  public function getAddress()
  {
    return $this->address;
  }

  /**
   * Set downloadLink.
   *
   * @param string $downloadLink
   *
   * @return Document
   */
  public function setDownloadLink($downloadLink)
  {
    $this->downloadLink = $downloadLink;

    return $this;
  }

  /**
   * Get downloadLink.
   *
   * @return string
   */
  public function getDownloadLink()
  {
    return $this->downloadLink;
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
   * @SWG\Property(description="Tenant identifier", type="string", format="uuid")
   * @Groups({"read"})
   *
   */
  public function getTenantId(): string
  {
    return $this->getTenant()->getId();
  }

  /**
   * Set title.
   *
   * @param string $title
   *
   * @return Document
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
   * @param Categoria[] $topics
   *
   * @return $this
   */
  public function setTopics($topics)
  {
    $this->topics = $topics;

    return $this;
  }

  /**
   * @return Collection
   */
  public function getTopics()
  {
    return $this->topics;
  }

  /**
   * @Serializer\VirtualProperty(name="topics")
   * @Serializer\Type("array<string>")
   * @Serializer\SerializedName("topics")
   * @SWG\Property(description="Document topics", type="array", @SWG\Items(type="string",format="uuid"))
   * @Groups({"read", "write"})
   *
   */
  public function getTopicsId(): array
  {
    $topics = [];
    foreach ($this->getTopics() as $topic)
    {
      $topics[] = $topic->getId();
    }
    return $topics;
  }

  /**
   * Set description.
   *
   * @param string|null $description
   *
   * @return Document
   */
  public function setDescription($description = null)
  {
    $this->description = $description;

    return $this;
  }

  /**
   * Get description.
   *
   * @return string|null
   */
  public function getDescription()
  {
    return $this->description;
  }

  /**
   * Set readersAllowed.
   *
   * @param string $readersAllowed
   *
   * @return Document
   */
  public function setReadersAllowed($readersAllowed)
  {
    $this->readersAllowed = $readersAllowed;

    return $this;
  }

  /**
   * Get readersAllowed.
   *
   * @return string
   */
  public function getReadersAllowed()
  {
    return $this->readersAllowed;
  }

  /**
   * Set lastReadAt.
   *
   * @param \DateTime|null $lastReadAt
   *
   * @return Document
   */
  public function setLastReadAt($lastReadAt = null)
  {
    $this->lastReadAt = $lastReadAt;

    return $this;
  }

  /**
   * Get lastReadAt.
   *
   * @return \DateTime|null
   */
  public function getLastReadAt()
  {
    return $this->lastReadAt;
  }

  /**
   * Set downloadsCounter.
   *
   * @param int $downloadsCounter
   *
   * @return Document
   */
  public function setDownloadsCounter($downloadsCounter)
  {
    $this->downloadsCounter = $downloadsCounter;

    return $this;
  }

  /**
   * Get downloadsCounter.
   *
   * @return int
   */
  public function getDownloadsCounter()
  {
    return $this->downloadsCounter;
  }

  /**
   * Set validityBegin.
   *
   * @param \DateTime $validityBegin
   *
   * @return Document
   */
  public function setValidityBegin($validityBegin)
  {
    $this->validityBegin = $validityBegin;

    return $this;
  }

  /**
   * Get validityBegin.
   *
   * @return \DateTime
   */
  public function getValidityBegin()
  {
    return $this->validityBegin;
  }

  /**
   * Set validityEnd.
   *
   * @param \DateTime $validityEnd
   *
   * @return Document
   */
  public function setValidityEnd($validityEnd)
  {
    $this->validityEnd = $validityEnd;

    return $this;
  }

  /**
   * Get validityEnd.
   *
   * @return \DateTime
   */
  public function getValidityEnd()
  {
    return $this->validityEnd;
  }

  /**
   * Set expireAt.
   *
   * @param \DateTime $expireAt
   *
   * @return Document
   */
  public function setExpireAt($expireAt)
  {
    $this->expireAt = $expireAt;

    return $this;
  }

  /**
   * Get expireAt.
   *
   * @return \DateTime
   */
  public function getExpireAt()
  {
    return $this->expireAt;
  }

  /**
   * Set dueDate.
   *
   * @param \DateTime $dueDate
   *
   * @return Document
   */
  public function setDueDate($dueDate)
  {
    $this->dueDate = $dueDate;

    return $this;
  }

  /**
   * Get dueDate.
   *
   * @return \DateTime
   */
  public function getDueDate()
  {
    return $this->dueDate;
  }

  /**
   * @param Servizio[] $correlatedServices
   *
   * @return $this
   */
  public function setCorrelatedServices($correlatedServices)
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
   * @SWG\Property(description="Document correlated services", type="array", @SWG\Items(type="string",format="uuid"))
   * @Groups({"read", "write"})
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
   * @param \DateTimeInterface $created_at
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
    if ($this->getExpireAt() === null) {

      $this->setExpireAt((new DateTime('now'))->modify('+5 years'));
    }
  }

  /**
   * Increment version
   *
   * @ORM\PreUpdate
   * @param PreUpdateEventArgs $event
   */
  public
  function preUpdate(PreUpdateEventArgs $event)
  {
    if (!$event->hasChangedField('downloadsCounter')) {
      $this->setVersion($this->version + 1);
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
