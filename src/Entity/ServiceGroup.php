<?php


namespace App\Entity;

use App\Model\PublicFile;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\OrderBy;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use JMS\Serializer\Annotation\Groups;
use Nelmio\ApiDocBundle\Annotation\Model;
use Ramsey\Uuid\Uuid;
use OpenApi\Annotations as OA;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as Serializer;
use Gedmo\Translatable\Translatable;

/**
 * ServiceGroup
 *
 * @ORM\Entity(repositoryClass="App\Entity\ServiceGroupRepository")
 * @ORM\Table(name="service_group",)
 * @ORM\HasLifecycleCallbacks
 *
 */
class ServiceGroup implements Translatable
{
  /**
   * Hook timestampable behavior
   * updates createdAt, updatedAt fields
   */
  use TimestampableEntity;

  /**
   * @ORM\Column(type="guid")
   * @ORM\Id
   * @OA\Property(description="Service's uuid")
   * @Groups({"read"})
   */
  protected $id;

  /**
   * @var string
   * @Gedmo\Translatable
   * @ORM\Column(type="string", length=255, unique=true)
   * @Assert\NotBlank(message="name")
   * @Assert\NotNull()
   * @OA\Property(description="Service's name")
   * @Groups({"read", "write"})
   */
  private $name;

  /**
   * @var string
   *
   * @Gedmo\Slug(fields={"name"})
   * @ORM\Column(type="string", length=255)
   * @OA\Property(description="Human-readable unique identifier, if empty will be generated from service's name")
   * @Groups({"read"})
   */
  private $slug;

  /**
   * @var string
   * @Gedmo\Translatable
   * @ORM\Column(type="text", nullable=true)
   * @OA\Property(description="Services group description")
   * @Groups({"read", "write"})
   */
  private $description;

  /**
   * @var string
   * @Gedmo\Translatable
   * @ORM\Column(type="string", nullable=true)
   * @OA\Property(description="Services group subtitle")
   * @Groups({"read", "write"})
   * @Assert\Length(max="160")
   * @Assert\NotBlank(message="service.short_description.not_blank")
   */
  private $shortDescription;

  /**
   * @var string
   * @Gedmo\Translatable
   * @ORM\Column(type="text", nullable=true)
   * @OA\Property(description="Compilation guide, accepts html tags")
   * @Groups({"read", "write"})
   */
  private $howto;

  /**
   * @var string
   * @Gedmo\Translatable
   * @ORM\Column(type="text", nullable=true)
   * @OA\Property(description="How to fill in the application")
   * @Groups({"read", "write"})
   */
  private $howToDo;

  /**
   * @var string
   * @Gedmo\Translatable
   * @ORM\Column(type="text", nullable=true)
   * @OA\Property(description="What you need to fill in the application")
   * @Groups({"read", "write"})
   */
  private $whatYouNeed;

  /**
   * @var string
   * @Gedmo\Translatable
   * @ORM\Column(type="text", nullable=true)
   * @OA\Property(description="The outcome of the application")
   * @Groups({"read", "write"})
   */
  private $whatYouGet;

  /**
   * @var string
   * @Gedmo\Translatable
   * @ORM\Column(type="text", nullable=true)
   * @OA\Property(description="Costs of this application")
   * @Groups({"read", "write"})
   */
  private $costs;

  /**
   * @ORM\Column(type="json", options={"jsonb":true}, nullable=true)
   * @var PublicFile[]
   * @OA\Property(
   *   description="Costs' attachments, list of filenames", type="array", @OA\Items(ref=@Model(type="object")))
   * @Groups({"read"})
   */
  private $costsAttachments;

  /**
   * @var string
   * @Gedmo\Translatable
   * @ORM\Column(type="text", nullable=true)
   * @OA\Property(description="Textual description of whom the service is addressed, accepts html tags")
   * @Groups({"read", "write"})
   */
  private $who;

  /**
   * @var string
   * @Gedmo\Translatable
   * @ORM\Column(type="text", nullable=true)
   * @OA\Property(description="Textual description of any special cases for obtaining the service, accepts html tags")
   * @Groups({"read", "write"})
   */
  private $specialCases;

  /**
   * @var string
   * @Gedmo\Translatable
   * @ORM\Column(type="text", nullable=true)
   * @OA\Property(description="Other info, accepts html tags")
   * @Groups({"read", "write"})
   */
  private $moreInfo;


  /**
   * @var string
   * @Gedmo\Translatable
   * @ORM\Column(type="text", nullable=true)
   * @OA\Property(description="Any restrictions on access to the service, accepts html tags")
   * @Groups({"read", "write"})
   */
  private $constraints;

  /**
   * @var string
   * @Gedmo\Translatable
   * @ORM\Column(type="text", nullable=true)
   * @OA\Property(description="Service times and deadlines, accepts html tags")
   */
  private $timesAndDeadlines;

  /**
   * @var string
   * @Gedmo\Translatable
   * @ORM\Column(type="text", nullable=true)
   * @OA\Property(description="Service group conditions, accepts html tags")
   * @Groups({"read", "write"})
   */
  private $conditions;

  /**
   * @ORM\Column(type="json", options={"jsonb":true}, nullable=true)
   * @var PublicFile[]
   * @OA\Property(
   *   description="Conditions' attachments, list of filenames", type="array", @OA\Items(ref=@Model(type="object")))
   * @Groups({"read"})
   */
  private $conditionsAttachments;

  /**
   * @var string[]
   * @ORM\Column(type="array", nullable=true)
   * @OA\Property(description="Geographical area covered by service", type="array", @OA\Items(type="string"))
   * @Groups({"read", "write"})
   */
  private $coverage;

  /**
   * @var bool
   * @ORM\Column(type="boolean", nullable=true)
   * @OA\Property(description="If selected the service group will be shown at the top of the page")
   * @Groups({"read", "write"})
   */
  private $sticky;

  /**
   * @var bool
   * @ORM\Column(type="boolean", nullable=true)
   * @OA\Property(description="Set true if application of  of this service group need to be registerd in folders")
   * @Groups({"read", "write"})
   */
  private $registerInFolder;

  /**
   * @ORM\OneToMany(targetEntity="App\Entity\Servizio", mappedBy="serviceGroup", cascade={"persist"})
   * @OrderBy({"name" = "ASC"})
   * @Serializer\Exclude()
   */
  private $services;

  /**
   * @ORM\OneToMany(targetEntity="App\Entity\Pratica", mappedBy="serviceGroup", cascade={"persist"})
   * @Serializer\Exclude()
   */
  private $applications;

  /**
   * @ORM\ManyToOne(targetEntity="Categoria", inversedBy="servicesGroup")
   * @Serializer\Exclude()
   */
  private $topics;

  /**
   * @ORM\ManyToMany(targetEntity="App\Entity\Recipient", inversedBy="servicesGroup")
   * @Serializer\Exclude
   * @var ArrayCollection
   */
  private $recipients;

  /**
   * @ORM\ManyToMany(targetEntity="App\Entity\GeographicArea", inversedBy="servicesGroup")
   * @Serializer\Exclude
   * @var ArrayCollection
   */
  private $geographicAreas;

  /**
   * @ORM\Column(type="json", options={"jsonb":true}, nullable=true)
   * @var array
   * @OA\Property(
   *   description="Linked life events from https://ontopia-lodview.agid.gov.it/controlled-vocabulary/classifications-for-public-services/life-business-event/life-event",
   *   type="array", @OA\Items(type="string", example="https://ontopia-lodview.agid.gov.it/controlled-vocabulary/classifications-for-public-services/life-business-event/life-event/1"))
   * @Groups({"read", "write"})
   */
  private $lifeEvents;

  /**
   * @ORM\Column(type="json", options={"jsonb":true}, nullable=true)
   * @var array
   * @OA\Property(description="Linked business events from https://ontopia-lodview.agid.gov.it/controlled-vocabulary/classifications-for-public-services/life-business-event/business-event",
   *   type="array", @OA\Items(type="string", example="https://ontopia-lodview.agid.gov.it/controlled-vocabulary/classifications-for-public-services/life-business-event/business-event/1"))
   * @Groups({"read", "write"})
   */
  private $businessEvents;

  /**
   * @ORM\Column(type="string", length=255, nullable=true)
   * @Assert\Url
   * @OA\Property(description="External service card url")
   */
  private $externalCardUrl;

  /**
   * @Gedmo\Locale
   * Used locale to override Translation listener`s locale
   * this is not a mapped field of entity metadata, just a simple property
   */
  private $locale;

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

    $this->lifeEvents = new ArrayCollection();
    $this->businessEvents = new ArrayCollection();

    $this->conditionsAttachments = new ArrayCollection();
    $this->costsAttachments = new ArrayCollection();
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
  public function getShortDescription(): ?string
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
   * @return string|null
   */
  public function getConstraints(): ?string
  {
    return $this->constraints;
  }

  /**
   * @param string|null $constraints
   */
  public function setConstraints(?string $constraints)
  {
    $this->constraints = $constraints;
  }

  /**
   * @return string|null
   */
  public function getTimesAndDeadlines(): ?string
  {
    return $this->timesAndDeadlines;
  }

  /**
   * @param string|null $timesAndDeadlines
   */
  public function setTimesAndDeadlines(?string $timesAndDeadlines)
  {
    $this->timesAndDeadlines = $timesAndDeadlines;
  }

  /**
   * @return string|null
   */
  public function getConditions(): ?string
  {
    return $this->conditions;
  }

  /**
   * @param string|null $conditions
   */
  public function setConditions(?string $conditions)
  {
    $this->conditions = $conditions;
  }

  /**
   * @return ArrayCollection
   */
  public function getConditionsAttachments(): ArrayCollection
  {
    if (!$this->conditionsAttachments instanceof ArrayCollection) {
      $this->conditionsAttachments = new ArrayCollection($this->conditionsAttachments);
    }
    return $this->conditionsAttachments;
  }

  /**
   * @param string $name
   * @return PublicFile|null
   */
  public function getConditionAttachmentByName(string $name): ?PublicFile
  {
    foreach ($this->getConditionsAttachments() as $attachment) {
      if ($attachment->getName() === $name) {
        return $attachment;
      }
    }
    return null;
  }

  /**
   * @param ArrayCollection $conditionsAttachments
   * @return $this
   */
  public function setConditionsAttachments(ArrayCollection $conditionsAttachments): ServiceGroup
  {
    $this->conditionsAttachments = $conditionsAttachments;
    return $this;
  }

  /**
   * @param PublicFile $attachment
   *
   * @return $this
   */
  public function addConditionsAttachment(PublicFile $attachment): ServiceGroup
  {
    if (!$this->conditionsAttachments->contains($attachment)) {
      $this->conditionsAttachments->add($attachment);
    }

    return $this;
  }

  /**
   * @param PublicFile $attachment
   *
   * @return $this
   */
  public function removeConditionsAttachment(PublicFile $attachment): ServiceGroup
  {
    if ($this->conditionsAttachments->contains($attachment)) {
      $this->conditionsAttachments->removeElement($attachment);
    }

    return $this;
  }

  /**
   * @return string[]
   */
  public function getCoverage()
  {
    if (is_array($this->coverage)) {
      return $this->coverage;
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
   * @Serializer\VirtualProperty()
   * @Serializer\SerializedName("services_count")
   * @Serializer\Type("integer")
   * @OA\Property(description="Related services count", type="array", @OA\Items(type="integer"))
   * @Groups({"read"})
   */
  public function getServicesCount()
  {
    return $this->services->count();
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
   * @OA\Property(description="Service's topic (uuid)")
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
   * @Serializer\Type("array")
   * @OA\Property(description="Service's recipients object defines an id and name", type="array", @OA\Items(type="object"))
   * @Groups({"read", "write"})
   */
  public function getRecipientsIds()
  {
    $recipients = [];
    /** @var Recipient $r */
    foreach ($this->recipients as $r) {
      $recipients []= ['id' => $r->getId(), 'name' => $r->getName()];
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
   * @Serializer\Type("array")
   * @OA\Property(description="Service's geographic areas object defines an id and name", type="array", @OA\Items(type="object"))
   * @Groups({"read", "write"})
   */
  public function getGeographicAreasIds()
  {
    $geographicAreas = [];
    /** @var Recipient $r */
    foreach ($this->geographicAreas as $r) {
      $geographicAreas []= ['id' => $r->getId(), 'name' => $r->getName()];
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

  public function setTranslatableLocale($locale)
  {
    $this->locale = $locale;
  }


  /**
   * Get the value of howToDo
   *
   * @return  string
   */
  public function getHowToDo()
  {
    return $this->howToDo;
  }

  /**
   * Set the value of howToDo
   *
   * @param  string  $howToDo
   *
   * @return  self
   */
  public function setHowToDo(?string $howToDo)
  {
    $this->howToDo = $howToDo;

    return $this;
  }

  /**
   * Get the value of whatYouNeed
   *
   * @return  string
   */
  public function getWhatYouNeed()
  {
    return $this->whatYouNeed;
  }

  /**
   * Set the value of whatYouNeed
   *
   * @param  string  $whatYouNeed
   *
   * @return  self
   */
  public function setWhatYouNeed(?string $whatYouNeed)
  {
    $this->whatYouNeed = $whatYouNeed;

    return $this;
  }

  /**
   * Get the value of whatYouGet
   *
   * @return  string
   */
  public function getWhatYouGet()
  {
    return $this->whatYouGet;
  }

  /**
   * Set the value of whatYouGet
   *
   * @param  string  $whatYouGet
   *
   * @return  self
   */
  public function setWhatYouGet(?string $whatYouGet)
  {
    $this->whatYouGet = $whatYouGet;

    return $this;
  }

  /**
   * Get the value of costs
   *
   * @return  string
   */
  public function getCosts()
  {
    return $this->costs;
  }

  /**
   * Set the value of costs
   *
   * @param  string  $costs
   *
   * @return  self
   */
  public function setCosts(?string $costs)
  {
    $this->costs = $costs;

    return $this;
  }

  /**
   * @return ArrayCollection
   */
  public function getCostsAttachments(): ArrayCollection
  {
    if (!$this->costsAttachments instanceof ArrayCollection) {
      $this->costsAttachments = new ArrayCollection($this->costsAttachments);
    }
    return $this->costsAttachments;
  }

  /**
   * @param string $name
   * @return PublicFile|null
   */
  public function getCostAttachmentByName(string $name): ?PublicFile
  {
    foreach ($this->getCostsAttachments() as $attachment) {
      if ($attachment->getName() === $name) {
        return $attachment;
      }
    }
    return null;
  }

  /**
   * @param ArrayCollection $costsAttachments
   * @return $this
   */
  public function setCostsAttachments(ArrayCollection $costsAttachments): ServiceGroup
  {
    $this->costsAttachments = $costsAttachments;
    return $this;
  }

  /**
   * @param PublicFile $attachment
   *
   * @return $this
   */
  public function addCostsAttachment(PublicFile $attachment): ServiceGroup
  {
    if (!$this->costsAttachments->contains($attachment)) {
      $this->costsAttachments->add($attachment);
    }

    return $this;
  }

  /**
   * @param PublicFile $attachment
   *
   * @return $this
   */
  public function removeCostsAttachment(PublicFile $attachment): ServiceGroup
  {
    if ($this->costsAttachments->contains($attachment)) {
      $this->costsAttachments->removeElement($attachment);
    }

    return $this;
  }

  /**
   * @return array
   */
  public function getLifeEvents()
  {
    return [];
    return $this->lifeEvents ?? [];
  }

  /**
   * @param array $lifeEvents
   * @return $this
   */
  public function setLifeEvents(array $lifeEvents = []): ServiceGroup
  {
    $this->lifeEvents = $lifeEvents;
    return $this;
  }

  /**
   * @return array
   */
  public function getBusinessEvents()
  {
    return [];
    return $this->businessEvents ?? [];
  }

  /**
   * @param array $businessEvents
   * @return $this
   */
  public function setBusinessEvents(array $businessEvents = []): ServiceGroup
  {
    $this->businessEvents = $businessEvents;
    return $this;
  }

  /**
   * @return mixed
   */
  public function getExternalCardUrl()
  {
    return $this->externalCardUrl;
  }

  /**
   * @param mixed $externalCardUrl
   */
  public function setExternalCardUrl($externalCardUrl): void
  {
    $this->externalCardUrl = $externalCardUrl;
  }

  /**
   * @ORM\PreFlush()
   */
  public function toArray()
  {
    $this->conditionsAttachments = $this->getConditionsAttachments()->toArray();
    $this->costsAttachments = $this->getCostsAttachments()->toArray();
  }

  /**
   * @ORM\PostLoad()
   * @ORM\PostUpdate()
   */
  public function toArrayCollection()
  {
    $conditionsAttachments = new ArrayCollection();
    $costsAttachments = new ArrayCollection();

    $attachments = array_merge($this->conditionsAttachments ?? [], $this->costsAttachments ?? []);
    if ($attachments) {
      foreach ($attachments as $attachment) {
        if (!$attachment instanceof PublicFile) {
          $publicAttachment = new PublicFile();
          $publicAttachment->setName($attachment['name']);
          $publicAttachment->setOriginalName($attachment['original_name']);
          $publicAttachment->setMimeType($attachment['mime_type']);
          $publicAttachment->setSize($attachment['size']);
          $publicAttachment->setType($attachment['type']);
        } else {
          $publicAttachment = $attachment;
        }

        if ($publicAttachment->getType() === PublicFile::CONDITIONS_TYPE) {
          $conditionsAttachments->add($publicAttachment);
        } elseif ($publicAttachment->getType() === PublicFile::COSTS_TYPE) {
          $costsAttachments->add($publicAttachment);
        }
      }
    }

    $this->conditionsAttachments = $conditionsAttachments;
    $this->costsAttachments = $costsAttachments;
  }
}
