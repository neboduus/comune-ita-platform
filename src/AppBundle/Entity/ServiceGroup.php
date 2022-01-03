<?php


namespace AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\OrderBy;
use JMS\Serializer\Annotation\Groups;
use Ramsey\Uuid\Uuid;
use Swagger\Annotations as SWG;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as Serializer;

/**
 * ServiceGroup
 *
 * @ORM\Entity(repositoryClass="AppBundle\Entity\ServiceGroupRepository")
 * @ORM\Table(name="service_group",)
 *
 */
class ServiceGroup
{

  /**
   * @ORM\Column(type="guid")
   * @ORM\Id
   * @SWG\Property(description="Service's uuid")
   * @Groups({"read"})
   */
  protected $id;

  /**
   * @var string
   *
   * @ORM\Column(type="string", length=255, unique=true)
   * @Assert\NotBlank(message="name")
   * @Assert\NotNull()
   * @SWG\Property(description="Service's name")
   * @Groups({"read", "write"})
   */
  private $name;

  /**
   * @var string
   *
   * @Gedmo\Slug(fields={"name"})
   * @ORM\Column(type="string", length=255)
   * @SWG\Property(description="Human-readable unique identifier, if empty will be generated from service's name")
   * @Groups({"read"})
   */
  private $slug;

  /**
   * @var string
   * @ORM\Column(type="text", nullable=true)
   * @SWG\Property(description="Services group description")
   * @Groups({"read", "write"})
   */
  private $description;

  /**
   * @var string
   * @ORM\Column(type="text", nullable=true)
   * @SWG\Property(description="Compilation guide, accepts html tags")
   * @Groups({"read", "write"})
   */
  private $howto;

  /**
   * @var string
   * @ORM\Column(type="text", nullable=true)
   * @SWG\Property(description="Textual description of whom the service is addressed, accepts html tags")
   * @Groups({"read", "write"})
   */
  private $who;

  /**
   * @var string
   * @ORM\Column(type="text", nullable=true)
   * @SWG\Property(description="Textual description of any special cases for obtaining the service, accepts html tags")
   * @Groups({"read", "write"})
   */
  private $specialCases;

  /**
   * @var string
   * @ORM\Column(type="text", nullable=true)
   * @SWG\Property(description="Other info, accepts html tags")
   * @Groups({"read", "write"})
   */
  private $moreInfo;

  /**
   * @var string[]
   * @ORM\Column(type="array", nullable=true)
   * @SWG\Property(description="Geographical area covered by service", type="array", @SWG\Items(type="string"))
   * @Groups({"read", "write"})
   */
  private $coverage;

  /**
   * @var bool
   * @ORM\Column(type="boolean", nullable=true)
   * @SWG\Property(description="If selected the service group will be shown at the top of the page")
   * @Groups({"read", "write"})
   */
  private $sticky;

  /**
   * @var bool
   * @ORM\Column(type="boolean", nullable=true)
   * @SWG\Property(description="Set true if application of  of this service group need to be registerd in folders")
   * @Groups({"read", "write"})
   */
  private $registerInFolder;

  /**
   * @ORM\OneToMany(targetEntity="AppBundle\Entity\Servizio", mappedBy="serviceGroup", cascade={"persist"})
   * @OrderBy({"name" = "ASC"})
   * @Serializer\Exclude()
   */
  private $services;

  /**
   * @ORM\OneToMany(targetEntity="AppBundle\Entity\Pratica", mappedBy="serviceGroup", cascade={"persist"})
   * @Serializer\Exclude()
   */
  private $applications;

  /**
   * @ORM\ManyToOne(targetEntity="Categoria", inversedBy="servicesGroup")
   * @Serializer\Exclude()
   */
  private $topics;

  /**
   * @ORM\ManyToMany(targetEntity="AppBundle\Entity\Recipient")
   * @Serializer\Exclude
   * @var ArrayCollection
   */
  private $recipients;

  /**
   * @ORM\ManyToMany(targetEntity="AppBundle\Entity\GeographicArea", inversedBy="services")
   * @Serializer\Exclude
   * @var ArrayCollection
   */
  private $geographicAreas;

  /**
   * ServiceGroup constructor.
   */
  public function __construct()
  {
    if (!$this->id) {
      $this->id = Uuid::uuid4();
    }
    $this->services = new ArrayCollection();
    $this->recipients = new ArrayCollection();
    $this->geographicAreas = new ArrayCollection();
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
  public function setId($id)
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
  public function setName(string $name)
  {
    $this->name = $name;
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
  }

  /**
   * @return string
   */
  public function getHowto()
  {
    return $this->howto;
  }

  /**
   * @param string $howto
   *
   * @return ServiceGroup
   */
  public function setHowto($howto)
  {
    $this->howto = $howto;

    return $this;
  }

  /**
   * @return string
   */
  public function getWho()
  {
    return $this->who;
  }

  /**
   * @param string|null $who
   */
  public function setWho($who)
  {
    $this->who = $who;
  }

  /**
   * @return string
   */
  public function getSpecialCases()
  {
    return $this->specialCases;
  }

  /**
   * @param string $specialCases
   */
  public function setSpecialCases($specialCases)
  {
    $this->specialCases = $specialCases;
  }

  /**
   * @return string
   */
  public function getMoreInfo()
  {
    return $this->moreInfo;
  }

  /**
   * @param string $moreInfo
   */
  public function setMoreInfo($moreInfo)
  {
    $this->moreInfo = $moreInfo;
  }

  /**
   * @return string
   */
  public function getCoverage()
  {
    if (is_array($this->coverage)) {
      return array_filter($this->coverage);
    } else {
      return array_filter(explode(',', $this->coverage));
    }
  }

  /**
   * @param $coverage
   */
  public function setCoverage($coverage)
  {
    $this->coverage = $coverage;
  }

  /**
   * @return bool
   */
  public function isSticky()
  {
    return $this->sticky;
  }

  /**
   * @param bool $sticky
   * @return $this
   */
  public function setSticky($sticky)
  {
    $this->sticky = $sticky;
    return $this;
  }

  /**
   * @return bool
   */
  public function isRegisterInFolder(): ?bool
  {
    return $this->registerInFolder;
  }

  /**
   * @param bool $registerInFolder
   */
  public function setRegisterInFolder(bool $registerInFolder)
  {
    $this->registerInFolder = $registerInFolder;
  }

  /**
   * @return mixed
   */
  public function getServices()
  {
    return $this->services;
  }

  /**
   * @return mixed
   */
  public function getPublicServices()
  {
    $result = new ArrayCollection();
    /** @var Servizio $service */
    foreach ($this->services as $service) {
      if ($service->getStatus() == Servizio::STATUS_AVAILABLE || $service->getStatus() == Servizio::STATUS_SUSPENDED || $service->getStatus() == Servizio::STATUS_SCHEDULED) {
        $result->add($service);
      }
    }
    return $result;
  }

  /**
   * @return mixed
   */
  public function getStickyServices()
  {
    $result = new ArrayCollection();
    /** @var Servizio $service */
    foreach ($this->services as $service) {
      // Only sticky services
      if ($service->isSticky() && $service->getStatus() !== Servizio::STATUS_CANCELLED && !$service->isSharedWithGroup()) {
        /*
         * For all STICKY services
         * If service group is private (i.e. all services are private) show all services
         * show only not private services otherwise
         */
        if ((!$this->isPrivate() && $service->getStatus() !== Servizio::STATUS_PRIVATE) || $this->isPrivate()) {
          $result->add($service);
        }
      }
    }

    return $result;

  }

  /**
   * @return mixed
   */
  public function getNotStickyServices()
  {
    $result = new ArrayCollection();
    /** @var Servizio $service */
    foreach ($this->services as $service) {
      /*
         * For all NOT STICKY services
         * If service group is private (i.e. all services are private) show all services
         * show only not private services otherwise
         */
      if (!$service->isSticky() && $service->getStatus() !== Servizio::STATUS_CANCELLED && !$service->isSharedWithGroup()) {
        if ((!$this->isPrivate() && $service->getStatus() !== Servizio::STATUS_PRIVATE) || $this->isPrivate() ) {
          $result->add($service);
        }
      }
    }

    return $result;

  }

  /**
   * @return mixed
   */
  public function getSharedServices()
  {
    $result = new ArrayCollection();
    /** @var Servizio $service */
    foreach ($this->getPublicServices() as $service) {
      // Only sticky services
      if ($service->isSharedWithGroup()) {
          $result->add($service);
      }
    }
    return $result;
  }

  public function isPrivate()
  {
    $private = true;
    /** @var Servizio $service */
    foreach ($this->services as $service) {
      if ($service->getStatus() !== Servizio::STATUS_PRIVATE) {
        $private = false;
      }
    }
    return $private;
  }

  /**
   * @param mixed $services
   */
  public function setServices($services)
  {
    $this->services = $services;
  }

  /**
   * @param Servizio $service
   */
  public function addService(Servizio $service)
  {
    if (!$this->services->contains($service)) {
      $this->services[] = $service;
    }
  }

  /**
   * @return mixed
   */
  public function getApplications()
  {
    return $this->applications;
  }

  /**
   * @param mixed $applications
   */
  public function setApplications($applications)
  {
    $this->applications = $applications;
  }

  /**
   * @return mixed
   */
  public function getTopics()
  {
    return $this->topics;
  }

  /**
   * @param mixed $topics
   * @return self
   */
  public function setTopics($topics)
  {
    $this->topics = $topics;
    return $this;
  }

  /**
   * @Serializer\VirtualProperty()
   * @Serializer\Type("string")
   * @Serializer\SerializedName("topics")
   * @SWG\Property(description="Service's topic (uuid)")
   * @Groups({"read", "write"})
   */
  public function getTopicsId()
  {
    if ($this->topics instanceof Categoria) {
      return $this->topics->getId();
    }
    return null;
  }

  /**
   * @Serializer\VirtualProperty()
   * @Serializer\SerializedName("recipients")
   * @Serializer\Type("array<string>")
   * @SWG\Property(description="Service's recipients id", type="array", @SWG\Items(type="string"))
   * @Groups({"read", "write"})
   */
  public function getRecipientsIds()
  {
    $recipients = [];
    /** @var Recipient $r */
    foreach ($this->recipients as $r) {
      $recipients []= $r->getId();
    }
    return $recipients;
  }

  /**
   * @return ArrayCollection
   */
  public function getRecipients()
  {
    return $this->recipients;
  }

  /**
   * @param Collection|null $recipients
   */
  public function setRecipients(?Collection $recipients): void
  {
    $this->recipients = $recipients;
  }

  /**
   * @param Recipient $recipient
   *
   * @return $this
   */
  public function addRecipient(Recipient $recipient)
  {
    if (!$this->recipients->contains($recipient)) {
      $this->recipients->add($recipient);
    }
    return $this;
  }

  /**
   * @param Recipient $recipient
   *
   * @return $this
   */
  public function removeRecipient(Recipient $recipient)
  {
    if ($this->recipients->contains($recipient)) {
      $this->recipients->removeElement($recipient);
    }
    return $this;
  }

  /**
   * @Serializer\VirtualProperty()
   * @Serializer\SerializedName("geographic_areas")
   * @Serializer\Type("array<string>")
   * @SWG\Property(description="Service's geographic areas id", type="array", @SWG\Items(type="string"))
   * @Groups({"read", "write"})
   */
  public function getGeographicAreasIds()
  {
    $geographicAreas = [];
    /** @var Recipient $r */
    foreach ($this->geographicAreas as $r) {
      $geographicAreas []= $r->getId();
    }
    return $geographicAreas;
  }

  /**
   * @return ArrayCollection
   */
  public function getGeographicAreas()
  {
    return $this->geographicAreas;
  }

  /**
   * @param Collection $geographicAreas
   */
  public function setGeographicAreas(?Collection $geographicAreas): void
  {
    $this->geographicAreas = $geographicAreas;
  }

  /**
   * @param GeographicArea $geographicArea
   *
   * @return $this
   */
  public function addGeographicArea(GeographicArea $geographicArea)
  {
    if (!$this->geographicAreas->contains($geographicArea)) {
      $this->geographicAreas->add($geographicArea);
    }
    return $this;
  }

  /**
   * @param GeographicArea $geographicArea
   *
   * @return $this
   */
  public function removeGeographicArea(GeographicArea $geographicArea)
  {
    if ($this->geographicAreas->contains($geographicArea)) {
      $this->geographicAreas->removeElement($geographicArea);
    }
    return $this;
  }

  public function isSharedGroup()
  {
    /** @var Servizio $s */
    foreach ($this->services as $s) {
      if (!$s->isSharedWithGroup()) {
        return false;
      }
    }
    return true;
  }

}
