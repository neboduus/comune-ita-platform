<?php

namespace App\Entity;

use App\Model\FeedbackMessage;
use App\Model\FeedbackMessagesSettings;
use App\Model\IOServiceParameters;
use App\Model\PublicFile;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use App\Model\PaymentParameters;
use App\Model\FlowStep;
use App\Model\ServiceSource;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Translatable\Translatable;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use JMS\Serializer\Annotation as Serializer;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Annotations as OA;

/**
 * @ORM\Entity(repositoryClass="App\Entity\ServizioRepository")
 * @ORM\Table(name="servizio")
 * @ORM\HasLifecycleCallbacks
 */
class Servizio implements Translatable
{

  const STATUS_CANCELLED = 0;
  const STATUS_AVAILABLE = 1;
  const STATUS_SUSPENDED = 2;
  const STATUS_PRIVATE = 3;
  const STATUS_SCHEDULED = 4;

  const PAYMENT_NOT_REQUIRED = 0;
  const PAYMENT_REQUIRED = 1;
  const PAYMENT_DEFERRED = 2;

  const PUBLIC_STATUSES = [Servizio::STATUS_AVAILABLE, Servizio::STATUS_SUSPENDED, Servizio::STATUS_SCHEDULED];

  const ACCESS_LEVEL_ANONYMOUS = 0;
  const ACCESS_LEVEL_SOCIAL = 1000;
  const ACCESS_LEVEL_SPID_L1 = 2000;
  const ACCESS_LEVEL_SPID_L2 = 3000;
  const ACCESS_LEVEL_CIE = 4000;

  const WORKFLOW_APPROVAL = 0;
  const WORKFLOW_FORWARD = 1;

  const FORMIO_SERVICE_CLASS = '\App\Entity\FormIO';

  /**
   * Hook timestampable behavior
   * updates createdAt, updatedAt fields
   */
  use TimestampableEntity;

  /**
   * @ORM\Column(type="guid")
   * @ORM\Id
   * @OA\Property(description="Service's uuid")
   */
  protected $id;

  /**
   * @var string
   * @Gedmo\Translatable
   * @ORM\Column(type="string", length=255)
   * @Assert\NotBlank(message="name")
   * @Assert\NotNull()
   * @Assert\Length(max="255")
   * @OA\Property(description="Service's name")
   */
  private $name;

  /**
   * @var string
   *
   * @Gedmo\Slug(fields={"name"})
   * @ORM\Column(type="string", length=255)
   * @OA\Property(description="Human-readable unique identifier, if empty will be generated from service's name")
   */
  private $slug;

  /**
   * @ORM\ManyToMany(targetEntity="Erogatore", inversedBy="servizi")
   * @ORM\JoinTable(
   *     name="servizio_erogatori",
   *     joinColumns={@ORM\JoinColumn(name="servizio_id", referencedColumnName="id")},
   *     inverseJoinColumns={@ORM\JoinColumn(name="erogatore_id", referencedColumnName="id")}
   * )
   * @var ArrayCollection
   * @Serializer\Exclude()
   */
  private $erogatori;

  /**
   * @ORM\ManyToOne(targetEntity="Ente")
   * @ORM\JoinColumn(name="ente_id", referencedColumnName="id")
   * @Serializer\Exclude()
   *
   */
  private $ente;

  /**
   * @ORM\ManyToOne(targetEntity="Categoria", inversedBy="services")
   * @ORM\JoinColumn(name="topics", referencedColumnName="id", nullable=false)
   * @Serializer\Exclude()
   */
  private $topics;

  /**
   * @var string
   * @Gedmo\Translatable
   * @ORM\Column(type="text", nullable=true)
   * @OA\Property(description="Service's description, accepts html tags")
   */
  private $description;


  /**
   * @var string
   * @Gedmo\Translatable
   * @ORM\Column(type="string", nullable=true)
   * @OA\Property(description="Service's subtitle")
   * @Assert\Length(max="160")
   * @Assert\NotBlank(message="service.short_description.not_blank")
   */
  private $shortDescription;


  /**
   * @var string
   * @Gedmo\Translatable
   * @ORM\Column(type="text", nullable=true)
   * @OA\Property(description="Compilation guide, accepts html tags")
   */
  private $howto;


  /**
   * @var string
   * @Gedmo\Translatable
   * @ORM\Column(type="text", nullable=true)
   * @OA\Property(description="How to fill in the application")
   */
  private $howToDo;


  /**
   * @var string
   * @Gedmo\Translatable
   * @ORM\Column(type="text", nullable=true)
   * @OA\Property(description="What you need to fill in the application")
   */
  private $whatYouNeed;


  /**
   * @var string
   * @Gedmo\Translatable
   * @ORM\Column(type="text", nullable=true)
   * @OA\Property(description="The outcome of the application")
   */
  private $whatYouGet;


  /**
   * @var string
   * @Gedmo\Translatable
   * @ORM\Column(type="text", nullable=true)
   * @OA\Property(description="Costs of this application")
   */
  private $costs;

  /**
   * @ORM\Column(type="json", options={"jsonb":true}, nullable=true)
   * @var PublicFile[]
   * @OA\Property(
   *   description="Costs' attachments, list of filenames", type="array", @OA\Items(ref=@Model(type=PublicFile::class)))
   */
  private $costsAttachments;


  /**
   * @var string
   * @Gedmo\Translatable
   * @ORM\Column(type="text", nullable=true)
   * @OA\Property(description="Textual description of whom the service is addressed, accepts html tags")
   */
  private $who;

  /**
   * @var string
   * @Gedmo\Translatable
   * @ORM\Column(type="text", nullable=true)
   * @OA\Property(description="Textual description of any special cases for obtaining the service, accepts html tags")
   */
  private $specialCases;

  /**
   * @var string
   * @Gedmo\Translatable
   * @ORM\Column(type="text", nullable=true)
   * @OA\Property(description="Other info, accepts html tags")
   */
  private $moreInfo;

  /**
   * @var string
   * @Gedmo\Translatable
   * @ORM\Column(type="text", nullable=true)
   * @OA\Property(description="Any restrictions on access to the service, accepts html tags")
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
   * @ORM\Column(type="string", length=255, nullable=true)
   * @OA\Property(description="Call to action for booking an appointment", type="string", example="https://www.example.com/booking")
   */
  private $bookingCallToAction;

  /**
   * @var string
   * @Gedmo\Translatable
   * @ORM\Column(type="text", nullable=true)
   * @OA\Property(description="Service conditions, accepts html tags")
   */
  private $conditions;

  /**
   * @ORM\Column(type="json", options={"jsonb":true}, nullable=true)
   * @var PublicFile[]
   * @OA\Property(
   *   description="Conditions' attachments, list of filenames", type="array", @OA\Items(ref=@Model(type=PublicFile::class)))
   */
  private $conditionsAttachments;

  /**
   * @var string
   * @Gedmo\Translatable
   * @ORM\Column(type="text", nullable=true)
   * @OA\Property(description="Information shown to the citizen during the compilation of the service, accepts html tags")
   */
  private $compilationInfo;

  /**
   * @var string
   * @Gedmo\Translatable
   * @ORM\Column(type="text", nullable=true)
   * @OA\Property(description="Indications shown to the citizen at the end of the compilation of the service, accepts html tags")
   */
  private $finalIndications;

  /**
   * @var string[]
   * @ORM\Column(type="array", nullable=true)
   * @OA\Property(description="Geographical area covered by service", type="array", @OA\Items(type="string"))
   */
  private $coverage;

  /**
   * @var string
   * @ORM\Column(type="string")
   * @Assert\NotBlank(message="identifier")
   * @Assert\NotNull()
   * @Serializer\Exclude()
   */
  private $praticaFCQN;

  /**
   * @var string
   * @ORM\Column(type="string")
   * @Assert\NotBlank(message="identifier")
   * @Assert\NotNull()
   * @Serializer\Exclude()
   */
  private $praticaFlowServiceName;

  /**
   * @var string
   * @ORM\Column(type="string", nullable=true)
   * @Serializer\Exclude()
   */
  private $praticaFlowOperatoreServiceName;

  /**
   * @var array
   * @ORM\Column(type="json", nullable=true)
   * @Serializer\Exclude()
   */
  private $additionalData;

  /**
   * @var FlowStep[]
   * @ORM\Column(type="json", nullable=true)
   * @OA\Property(property="flow_steps", type="array", @OA\Items(ref=@Model(type=FlowStep::class)))
   */
  private $flowSteps;

  /**
   * @var array
   * @ORM\Column(type="json", nullable=true)
   * @Serializer\Type("array<string, string>")
   */
  private $protocolloParameters;

  /**
   * @ORM\Column(type="integer", nullable=true, options={"default":"0"})
   * @OA\Property(description="Accepts values: 0 - Not required, 1 - Required, 2 - Deferred")
   */
  private $paymentRequired;

  /**
   * @var array
   * @ORM\Column(type="json", nullable=true)
   * @OA\Property(property="payment_parameters", description="List of payment gateways available for the service and related parameters", type="object", ref=@Model(type=PaymentParameters::class))
   */
  private $paymentParameters;

  /**
   * @var array
   * @ORM\Column(type="json", nullable=true)
   * @Serializer\Exclude()
   */
  private $integrations;

  /**
   * @var string
   * @ORM\Column(type="string", nullable=true)
   * @Serializer\Exclude()
   * @OA\Property(description="Accepts html characters")
   *
   */
  protected $handler;

  /**
   * @var bool
   * @ORM\Column(type="boolean", nullable=true)
   * @OA\Property(description="If selected the service will be shown at the top of the page")
   */
  private $sticky;

  /**
   * @ORM\Column(type="integer")
   * @Assert\NotBlank(message="identifier")
   * @Assert\NotNull()
   * @OA\Property(description="Accepts values: 0 - Hidden, 1 - Pubblished, 2 - Suspended")
   */
  private $status;

  /**
   * @ORM\Column(type="integer", nullable=true)
   * @OA\Property(description="Accepts values: 0 - Anonymous, 1000 - Social, 2000 - Spid level 1, 3000 - Spid level 2, 4000 - Cie")
   */
  private $accessLevel;

  /**
   * @var bool
   * @ORM\Column(name="login_suggested", type="boolean", nullable=true)
   * @OA\Property(description="Enable or disable the suggestion to log in to auto-complete some fields")
   */
  private $loginSuggested;

  /**
   * @var bool
   * @ORM\Column(type="boolean", nullable=true, options={"default":"1"})
   * @OA\Property(description="If selected the application will be registered in tenants protocol")
   */
  private $protocolRequired;

  /**
   * @var string
   * @ORM\Column(type="string", nullable=true)
   * @OA\Property(description="Service protocol handler: dummy, pec, pitre, infor")
   */
  private $protocolHandler;

  /**
   * @var FeedbackMessage[]
   * @Gedmo\Translatable
   * @ORM\Column(type="json", nullable=true)
   * @OA\Property(description="Service feedback messages")
   */
  private $feedbackMessages;

  /**
   * @var \DateTime
   * @ORM\Column(type="datetime", nullable=true)
   */
  private $scheduledFrom;

  /**
   * @var \DateTime
   * @ORM\Column(type="datetime", nullable=true)
   */
  private $scheduledTo;

  /**
   * @var string
   * @ORM\Column(type="text", nullable=true)
   * @Serializer\Exclude()
   */
  private $postSubmitValidationExpression;

  /**
   * @var string
   * @ORM\Column(type="string", nullable=true)
   * @Serializer\Exclude()
   */
  private $postSubmitValidationMessage;

  /**
   * @ORM\ManyToOne(targetEntity="App\Entity\ServiceGroup", inversedBy="services")
   * @ORM\JoinColumn(name="service_group_id", referencedColumnName="id", nullable=true)
   * @OA\Property(description="Service group id", type="string")
   */
  private $serviceGroup;

  /**
   * @var bool
   * @ORM\Column(type="boolean", nullable=true, options={"default":"0"})
   * @OA\Property(description="If selected the service will share the group's descriptipn")
   */
  private $sharedWithGroup;

  /**
   * @var bool
   * @ORM\Column(type="boolean", nullable=true, options={"default":"0"})
   * @OA\Property(description="If selected, service's applications can be reopend")
   */
  private $allowReopening;

  /**
   * @var bool
   * @ORM\Column(type="boolean", nullable=true, options={"default":"1"})
   * @OA\Property(description="If selected, service's applications can be withdraw")
   */
  private $allowWithdraw;

  /**
   * @var bool
   * @ORM\Column(type="boolean", nullable=true, options={"default":"0"})
   * @OA\Property(description="If selected, operator can request integrations for applications on this service")
   */
  private $allowIntegrationRequest;

  /**
   * @var integer
   * @ORM\Column(type="integer", nullable=true, options={"default":"0"})
   * @OA\Property(description="Service workflow type, accepts values: 0 - Approval, 1 - Forward")
   */
  private $workflow;

  /**
   * @var array
   * @ORM\Column(name="io_service_parameters", type="json", nullable=true)
   * @OA\Property(property="io_service_parameters", description="List of parameters required for integration with the io app", type="object", ref=@Model(type=IOServiceParameters::class))
   */
  private $IOServiceParameters;

  /**
   * @var string
   * @ORM\Column(type="string", nullable=true)
   */
  private $backofficeFormId;

  /**
   * @ORM\ManyToMany(targetEntity="App\Entity\Recipient", inversedBy="services")
   * @ORM\JoinTable(
   *     name="servizio_recipient",
   *     joinColumns={@ORM\JoinColumn(name="servizio_id", referencedColumnName="id")},
   *     inverseJoinColumns={@ORM\JoinColumn(name="recipient_id", referencedColumnName="id")}
   * )
   * @var ArrayCollection
   */
  private $recipients;

  /**
   * @ORM\ManyToMany(targetEntity="App\Entity\GeographicArea", inversedBy="services")
   * @ORM\JoinTable(
   *     name="servizio_geographic_area",
   *     joinColumns={@ORM\JoinColumn(name="servizio_id", referencedColumnName="id")},
   *     inverseJoinColumns={@ORM\JoinColumn(name="geographic_area_id", referencedColumnName="id")}
   * )
   * @var ArrayCollection
   */
  private $geographicAreas;

  /**
   * @var integer
   * @ORM\Column(type="integer", nullable=true)
   * @OA\Property(description="Maximum service delivery time in days. The service will be answered within <maxResponseTime> days.")
   */
  private $maxResponseTime;

  /**
   * @ORM\Column(type="json", options={"jsonb":true}, nullable=true)
   * @var array
   * @OA\Property(
   *   description="Linked life events from https://ontopia-lodview.agid.gov.it/controlled-vocabulary/classifications-for-public-services/life-business-event/life-event",
   *   type="array", @OA\Items(type="string", example="https://ontopia-lodview.agid.gov.it/controlled-vocabulary/classifications-for-public-services/life-business-event/life-event/1"))
   */
  private $lifeEvents;

  /**
   * @ORM\Column(type="json", options={"jsonb":true}, nullable=true)
   * @var array
   * @OA\Property(description="Linked business events from https://ontopia-lodview.agid.gov.it/controlled-vocabulary/classifications-for-public-services/life-business-event/business-event",
   *   type="array", @OA\Items(type="string", example="https://ontopia-lodview.agid.gov.it/controlled-vocabulary/classifications-for-public-services/life-business-event/business-event/1"))
   */
  private $businessEvents;

  /**
   * @var ServiceSource
   * @ORM\Column(type="json", nullable=true)
   * @OA\Property(property="source", description="Source of the service if imported", type="object", ref=@Model(type=ServiceSource::class))
   */
  private $source;

  /**
   * @ORM\Column(type="string", length=255, nullable=true)
   * @Assert\Url
   * @OA\Property(description="External service card url")
   */
  private $externalCardUrl;

  /**
   * @ORM\ManyToMany(targetEntity="App\Entity\UserGroup", mappedBy="services")
   * @var ArrayCollection
   * @Serializer\Exclude()
   */
  private $userGroups;

  /**
   * @var string
   * @ORM\Column(type="string", length=255, nullable=true, unique=true)
   * @Assert\Url
   * @OA\Property(description="Public service identifier")
   */
  private $identifier;

  /**
   * @Gedmo\Locale
   * Used locale to override Translation listener`s locale
   * this is not a mapped field of entity metadata, just a simple property
   */
  private $locale;

  /**
   * Servizio constructor.
   */
  public function __construct()
  {
    if (!$this->id) {
      $this->id = Uuid::uuid4();
    }

    $this->erogatori = new ArrayCollection();
    $this->flowSteps = new ArrayCollection();
    $this->feedbackMessages = new ArrayCollection();
    $this->recipients = new ArrayCollection();
    $this->geographicAreas = new ArrayCollection();
    //$this->paymentParameters = new ArrayCollection();
    $this->status = self::STATUS_AVAILABLE;
    $this->accessLevel = self::ACCESS_LEVEL_SPID_L2;
    $this->setSticky(false);
    $this->setWorkflow(self::WORKFLOW_APPROVAL);
    $this->setLoginSuggested(false);
    //$this->setProtocolRequired(true);
    $this->setAllowReopening(false);
    $this->setAllowWithdraw(true);
    $this->setAllowIntegrationRequest(true);
    $this->setFinalIndications('La domanda è stata correttamente registrata, non ti sono richieste altre operazioni. Grazie per la tua collaborazione.');

    $this->lifeEvents = array();
    $this->businessEvents = array();

    $this->conditionsAttachments = new ArrayCollection();
    $this->costsAttachments = new ArrayCollection();
    $this->userGroups = new ArrayCollection();
  }

  /**
   * @return string
   */
  public function __toString()
  {
    return (string)$this->getName();
  }

  /**
   * @return UuidInterface
   */
  public function getId()
  {
    return $this->id;
  }

  /**
   * @return mixed
   */
  public function getName()
  {
    return $this->name;
  }

  /**
   * @param string $name
   *
   * @return $this
   */
  public function setName($name)
  {
    $this->name = $name;

    return $this;
  }

  /**
   * @return string
   */
  public function getSlug()
  {
    return $this->slug;
  }

  /**
   * @param string $slug
   *
   * @return $this
   */
  public function setSlug($slug)
  {
    $this->slug = $slug;

    return $this;
  }

  /**
   * @param mixed $erogatori
   *
   * @return Servizio
   */
  public function setErogatori($erogatori)
  {
    $this->erogatori = $erogatori;

    return $this;
  }

  /**
   * @return Collection
   */
  public function getErogatori()
  {
    return $this->erogatori;
  }

  /**
   * @return Ente
   */
  public function getEnte()
  {
    return $this->ente;
  }

  /**
   * @param mixed $ente
   * @return self
   */
  public function setEnte($ente)
  {
    $this->ente = $ente;
    return $this;
  }

  /**
   * @return ArrayCollection
   */
  public function getUserGroups(): ArrayCollection
  {
    return $this->userGroups;
  }

  /**
   * @param ArrayCollection $userGroups
   */
  public function setUserGroups(ArrayCollection $userGroups): void
  {
    $this->userGroups = $userGroups;
  }

  /**
   * @return Ente[]
   */
  public function getEnti()
  {
    $enti = [];
    foreach ($this->erogatori as $erogatore) {
      foreach ($erogatore->getEnti() as $ente) {
        $enti[] = $ente;
      }
    }
    return $enti;
  }

  /**
   * Tenant identifier (uuid)
   *
   * @Serializer\VirtualProperty(name="tenant")
   * @Serializer\Type("string")
   * @Serializer\SerializedName("tenant")
   */
  public function getEnteId()
  {
    return $this->ente->getId();
  }

  /**
   * @return string
   */
  public function getDescription()
  {
    if ($this->serviceGroup != null && $this->sharedWithGroup) {
      return $this->serviceGroup->getDescription();
    }
    return $this->description;
  }

  /**
   * @param string $description
   *
   * @return $this
   */
  public function setDescription($description)
  {
    $this->description = $description;

    return $this;
  }


  /**
   * @return string
   */
  public function getShortDescription(): ?string
  {
    if ($this->serviceGroup != null && $this->sharedWithGroup) {
      return $this->serviceGroup->getShortDescription();
    }
    return $this->shortDescription;
  }

  /**
   * @param string $shortDescription
   *
   * @return $this
   */
  public function setShortDescription(string $shortDescription): Servizio
  {
    $this->shortDescription = $shortDescription;
    return $this;
  }

  /**
   * @return string
   */
  public function getHowto()
  {
    if ($this->serviceGroup != null && $this->sharedWithGroup) {
      return $this->serviceGroup->getHowto();
    }
    return $this->howto;
  }

  /**
   * @param string $howto
   *
   * @return Servizio
   */
  public function setHowto($howto)
  {
    $this->howto = $howto;

    return $this;
  }

  /**
   * @return mixed
   */
  public function getStatus()
  {
    return $this->status;
  }

  /**
   * @param $status
   *
   * @return $this
   */
  public function setStatus($status)
  {
    $this->status = $status;

    return $this;
  }

  /**
   * @return string
   */
  public function getPraticaFCQN()
  {
    return $this->praticaFCQN;
  }

  /**
   * @param string $praticaFCQN
   *
   * @return Servizio
   */
  public function setPraticaFCQN($praticaFCQN)
  {
    $this->praticaFCQN = $praticaFCQN;

    return $this;
  }

  /**
   * @return string
   */
  public function getPraticaFlowServiceName()
  {
    return $this->praticaFlowServiceName;
  }

  /**
   * @param string $praticaFlowServiceName
   *
   * @return Servizio
   */
  public function setPraticaFlowServiceName($praticaFlowServiceName)
  {
    $this->praticaFlowServiceName = $praticaFlowServiceName;

    return $this;
  }

  /**
   * @return string
   */
  public function getPraticaFlowOperatoreServiceName()
  {
    return $this->praticaFlowOperatoreServiceName;
  }

  /**
   * @param string $praticaFlowOperatoreServiceName
   *
   * @return Servizio
   */
  public function setPraticaFlowOperatoreServiceName($praticaFlowOperatoreServiceName)
  {
    $this->praticaFlowOperatoreServiceName = $praticaFlowOperatoreServiceName;

    return $this;
  }

  /**
   * Service's response type
   *
   * @Serializer\VirtualProperty()
   * @Serializer\Type("string")
   * @Serializer\SerializedName("response_type")
   * @OA\Property(description="Service's response type")
   */
  public function getResponseType()
  {
    return $this->praticaFlowOperatoreServiceName;
  }

  /**
   * @param Erogatore $erogatore
   * @return $this
   */
  public function activateForErogatore(Erogatore $erogatore)
  {
    if (!$this->erogatori->contains($erogatore)) {
      $this->erogatori->add($erogatore);
    }

    return $this;
  }

  public function isPaymentRequired(): bool
  {
    return $this->paymentRequired === self::PAYMENT_REQUIRED;
  }

  public function isPaymentDeferred(): bool
  {
    return $this->paymentRequired === self::PAYMENT_DEFERRED;
  }

  public function getPaymentRequired()
  {
    return $this->paymentRequired;
  }

  /**
   * @param $paymentRequired
   * @return $this;
   */
  public function setPaymentRequired($paymentRequired)
  {
    $this->paymentRequired = $paymentRequired;
    return $this;
  }

  /**
   * @return string
   */
  public function getHandler()
  {
    return $this->handler;
  }

  /**
   * @param string $handler
   * @return $this
   */
  public function setHandler(string $handler)
  {
    $this->handler = $handler;
    return $this;
  }

  /**
   * @return bool
   */
  public function isSticky(): ?bool
  {
    return $this->sticky;
  }

  /**
   * @param bool $sticky
   * @return $this
   */
  public function setSticky( $sticky )
  {
    $this->sticky = $sticky;
    return $this;
  }

  /**
   * @return array
   */
  public function getPaymentParameters()
  {
    $paymentParameters = $this->paymentParameters;
    if (isset($this->paymentParameters['gateways']) && !empty($this->paymentParameters['gateways'])) {
      $gateways = array();
      foreach ($this->paymentParameters['gateways'] as $k => $g) {
        if (is_string($g) ) {
          $gateways[$k] = json_decode($g, true);
        } else {
          $gateways[$k] = $g;
        }
      }
      $paymentParameters['gateways'] = $gateways;
    }
    return $paymentParameters;
  }

  /**
   * @param array $paymentParameters
   * @return $this
   */
  public function setPaymentParameters($paymentParameters)
  {
    $this->paymentParameters = $paymentParameters;
    return $this;
  }

  /**
   * @return array
   */
  public function getAdditionalData()
  {
    return $this->additionalData;
  }

  /**
   * @param array $additionalData
   * @return $this;
   *
   */
  public function setAdditionalData($additionalData): Servizio
  {
    $this->additionalData = $additionalData;
    return $this;
  }

  /**
   * @return array
   */
  public function getProtocolloParameters()
  {
    return $this->protocolloParameters;
  }

  /**
   * @param array $protocolloParameters
   * @return $this;
   */
  public function setProtocolloParameters($protocolloParameters): Servizio
  {
    $this->protocolloParameters = $protocolloParameters;
    return $this;
  }

  /**
   * @return mixed
   */
  public function getTopics()
  {
    if ($this->serviceGroup != null && $this->sharedWithGroup) {
      return $this->serviceGroup->getTopics();
    }
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
   */
  public function getTopicsId()
  {
    if ($this->getTopics()) {
      $this->getTopics()->getId();
    }
    return '';
  }

  /**
   * @return string
   */
  public function getWho()
  {
    if ($this->serviceGroup != null && $this->sharedWithGroup) {
      return $this->serviceGroup->getWho();
    }
    return $this->who;
  }

  /**
   * @param string|null $who
   */
  public function setWho( $who )
  {
    $this->who = $who;
  }

  /**
   * @return string
   */
  public function getSpecialCases()
  {
    if ($this->serviceGroup != null && $this->sharedWithGroup) {
      return $this->serviceGroup->getSpecialCases();
    }
    return $this->specialCases;
  }

  /**
   * @param string $specialCases
   */
  public function setSpecialCases( $specialCases )
  {
    $this->specialCases = $specialCases;
  }

  /**
   * @return string
   */
  public function getMoreInfo()
  {
    if ($this->serviceGroup != null && $this->sharedWithGroup) {
      return $this->serviceGroup->getMoreInfo();
    }
    return $this->moreInfo;
  }

  /**
   * @param string $moreInfo
   */
  public function setMoreInfo( $moreInfo )
  {
    $this->moreInfo = $moreInfo;
  }

  /**
   * @return string|null
   */
  public function getConstraints(): ?string
  {
    if ($this->serviceGroup != null && $this->sharedWithGroup) {
      return $this->serviceGroup->getConstraints();
    }
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
    if ($this->serviceGroup != null && $this->sharedWithGroup) {
      return $this->serviceGroup->getTimesAndDeadlines();
    }
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
  public function getBookingCallToAction(): ?string
  {
    return $this->bookingCallToAction;
  }

  /**
   * @param string|null $bookingCallToAction
   */
  public function setBookingCallToAction(?string $bookingCallToAction)
  {
    $this->bookingCallToAction = $bookingCallToAction;
  }

  /**
   * @return string|null
   */
  public function getConditions(): ?string
  {
    if ($this->serviceGroup != null && $this->sharedWithGroup) {
      return $this->serviceGroup->getConditions();
    }
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
    if ($this->serviceGroup != null && $this->sharedWithGroup) {
      return $this->serviceGroup->getConditionsAttachments();
    }
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
  public function setConditionsAttachments(ArrayCollection $conditionsAttachments): Servizio
  {
    $this->conditionsAttachments = $conditionsAttachments;
    return $this;
  }

  /**
   * @param PublicFile $attachment
   *
   * @return $this
   */
  public function addConditionsAttachment(PublicFile $attachment): Servizio
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
  public function removeConditionsAttachment(PublicFile $attachment): Servizio
  {
    if ($this->conditionsAttachments->contains($attachment)) {
      $this->conditionsAttachments->removeElement($attachment);
    }

    return $this;
  }

  /**
   * @return string
   */
  public function getCompilationInfo()
  {
    return $this->compilationInfo;
  }

  /**
   * @param string $compilationInfo
   */
  public function setCompilationInfo( $compilationInfo )
  {
    $this->compilationInfo = $compilationInfo;
  }


  /**
   * @return string
   */
  public function getFinalIndications()
  {
    return $this->finalIndications;
  }

  /**
   * @param string $finalIndications
   */
  public function setFinalIndications($finalIndications )
  {
    $this->finalIndications = $finalIndications;
  }

  /**
   * @return false|string[]
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
   * @return array
   */
  public function getFlowSteps()
  {
    $flowSteps = [];
    if ( count($this->flowSteps) > 0) {
      foreach ($this->flowSteps as $v) {
        if (is_string($v) ) {
          $v = json_decode($v, true);
        }
        $flowStep = new FlowStep();
        $flowStep->setIdentifier($v["identifier"]);
        $flowStep->setTitle($v["title"]);
        $flowStep->setType($v["type"]);
        $flowStep->setDescription($v["description"]);
        $flowStep->setGuide($v["guide"]);
        $flowStep->setParameters($v["parameters"]);

        $flowSteps[] = $flowStep;
      }
    }
    return $flowSteps;
  }

  /**
   * @param array $flowSteps
   */
  public function setFlowSteps($flowSteps)
  {
    //$this->flowSteps = $flowSteps;
    $this->flowSteps = array_map(function (FlowStep $flowStep) {
      return json_encode($flowStep);
    }, $flowSteps);

  }

  /**
   * @return string
   */
  public function getFormIoId()
  {
    $formID = '';
    $flowsteps = $this->getFlowSteps();
    if (!empty($flowsteps)) {
      foreach ($flowsteps as $f) {
        $parameters = $f->getParameters();
        if (!is_array($parameters)) {
          $parameters = \json_decode($parameters, true);
        }
        if ($f->getType() == 'formio' && isset($parameters['formio_id']) && !empty($parameters['formio_id'])) {
          $formID = $parameters['formio_id'];
          break;
        }
      }
    }
    // Retrocompatibilità
    if (!$formID) {
      $additionalData = $this->getAdditionalData();
      $formID = isset($additionalData['formio_id']) ? $additionalData['formio_id'] : false;
    }
    return $formID;
  }

  /**
   * @return array
   */
  public function getIntegrations()
  {
    return $this->integrations;
  }

  /**
   * @param array $integrations
   * @return $this;
   *
   */
  public function setIntegrations($integrations): Servizio
  {
    $this->integrations = $integrations;
    return $this;
  }

  /**
   * @return int
   */
  public function getAccessLevel()
  {
    return $this->accessLevel;
  }

  /**
   * @param int $accessLevel
   * @return $this;
   */
  public function setAccessLevel(int $accessLevel)
  {
    $this->accessLevel = $accessLevel;
    return $this;
  }

  /**
   * @return bool
   */
  public function isLoginSuggested()
  {
    return $this->loginSuggested;
  }

  /**
   * @param bool $loginSuggested
   * @return $this
   */
  public function setLoginSuggested( $loginSuggested )
  {
    $this->loginSuggested = $loginSuggested;
    return $this;
  }


  /**
   * @return bool
   */
  public function isProtocolRequired(): ?bool
  {
    return $this->protocolRequired;
  }

  /**
   * @param bool $protocolRequired
   */
  public function setProtocolRequired(?bool $protocolRequired)
  {
    $this->protocolRequired = $protocolRequired;
  }

  /**
   * @return string
   */
  public function getProtocolHandler(): ?string
  {
    return $this->protocolHandler;
  }

  /**
   * @param string $protocolHandler
   */
  public function setProtocolHandler(?string $protocolHandler): void
  {
    $this->protocolHandler = $protocolHandler;
  }

  /**
   * @return FeedbackMessage[]
   */
  public function getFeedbackMessages()
  {
    return $this->feedbackMessages;
  }

  /**
   * @param FeedbackMessage[] $feedbackMessages
   */
  public function setFeedbackMessages($feedbackMessages)
  {
    $messages = [];
    foreach ($feedbackMessages as $k => $feedbackMessage) {
      $messages [$feedbackMessage->getTrigger()] = $feedbackMessage;
    }
    $this->feedbackMessages = $messages;
  }

  public function getFeedbackMessagesSettings()
  {
    if (isset($this->additionalData[FeedbackMessagesSettings::KEY])) {
      return FeedbackMessagesSettings::fromArray($this->additionalData[FeedbackMessagesSettings::KEY]);
    }
    return null;
  }

  public function setFeedbackMessagesSettings( $settings )
  {
    $this->additionalData[FeedbackMessagesSettings::KEY] =  $settings;
    return $this;
  }

  /**
   * @return \DateTime
   */
  public function getScheduledFrom()
  {
    return $this->scheduledFrom;
  }

  /**
   * @param \DateTime|null $scheduledFrom
   */
  public function setScheduledFrom(?\DateTime $scheduledFrom)
  {
    $this->scheduledFrom = $scheduledFrom;
  }

  /**
   * @return \DateTime
   */
  public function getScheduledTo()
  {
    return $this->scheduledTo;
  }

  /**
   * @param \DateTime $scheduledTo
   */
  public function setScheduledTo(?\DateTime $scheduledTo)
  {
    $this->scheduledTo = $scheduledTo;
  }

  /**
   * @return string
   */
  public function getPostSubmitValidationExpression()
  {
    return $this->postSubmitValidationExpression;
  }

  /**
   * @param string $postSubmitValidationExpression
   */
  public function setPostSubmitValidationExpression($postSubmitValidationExpression)
  {
    $this->postSubmitValidationExpression = $postSubmitValidationExpression;
  }

  /**
   * @return string
   */
  public function getPostSubmitValidationMessage()
  {
    return $this->postSubmitValidationMessage;
  }

  /**
   * @param string $postSubmitValidationMessage
   */
  public function setPostSubmitValidationMessage($postSubmitValidationMessage)
  {
    $this->postSubmitValidationMessage = $postSubmitValidationMessage;
  }

  /**
   * @return mixed
   */
  public function getServiceGroup()
  {
    return $this->serviceGroup;
  }

  /**
   * @param mixed $serviceGroup
   */
  public function setServiceGroup(?ServiceGroup $serviceGroup)
  {
    if (!$serviceGroup) {
      $this->sharedWithGroup = false;
    }
    $this->serviceGroup = $serviceGroup;
  }

  /**
   * @return bool
   */
  public function isSharedWithGroup()
  {
    return $this->sharedWithGroup;
  }

  /**
   * @param bool $shared
   * @return $this
   */
  public function setSharedWithGroup( $shared )
  {
    $this->sharedWithGroup = $shared;
    return $this;
  }

  /**
   * @return bool
   */
  public function isAllowReopening(): ?bool
  {
    return $this->allowReopening;
  }

  /**
   * @param bool $allowReopening
   */
  public function setAllowReopening(?bool $allowReopening)
  {
    $this->allowReopening = $allowReopening;
  }

  /**
   * @return bool
   */
  public function isAllowWithdraw(): ?bool
  {
    return $this->allowWithdraw;
  }

  /**
   * @param bool $allowWithdraw
   */
  public function setAllowWithdraw(?bool $allowWithdraw)
  {
    $this->allowWithdraw = $allowWithdraw;
  }

  /**
   * @return bool
   */
  public function isAllowIntegrationRequest(): ?bool
  {
    return $this->allowIntegrationRequest;
  }

  /**
   * @param bool $allowIntegrationRequest
   */
  public function setAllowIntegrationRequest(?bool $allowIntegrationRequest): void
  {
    $this->allowIntegrationRequest = $allowIntegrationRequest;
  }

  /**
   * @return int
   */
  public function getWorkflow(): ?int
  {
    return $this->workflow;
  }

  /**
   * @param int $workflow
   */
  public function setWorkflow(?int $workflow)
  {
    $this->workflow = $workflow;
  }


  public function getIOServiceParameters(): ?array
  {
    return $this->IOServiceParameters;
  }

  /**
   * @param $IOParameters
   * @return $this
   */
  public function setIOServiceParameters($IOParameters): Servizio
  {
    $this->IOServiceParameters = $IOParameters;
    return $this;
  }

  public function isIOEnabled(): bool
  {
    $parameters = $this->getIOServiceParameters();
    if (!$parameters || !($parameters['IOserviceId'] && $parameters['primaryKey'])) return false;

    return true;
  }

  /**
   * @return string
   */
  public function getBackofficeFormId(): ?string
  {
    return $this->backofficeFormId;
  }

  /**
   * @param string|null $backofficeFormId
   */
  public function setBackofficeFormId(?string $backofficeFormId): void
  {
    $this->backofficeFormId = $backofficeFormId;
  }

  /**
   * @return ArrayCollection
   */
  public function getRecipients()
  {
    if ($this->serviceGroup != null && $this->sharedWithGroup) {
      return $this->serviceGroup->getRecipients();
    }
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
   * @return ArrayCollection
   */
  public function getGeographicAreas()
  {
    if ($this->serviceGroup != null && $this->sharedWithGroup) {
      return $this->serviceGroup->getGeographicAreas();
    }
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

  public function setTranslatableLocale($locale)
  {
    $this->locale = $locale;
  }

  public function isLegacy()
  {
    return $this->getPraticaFCQN() != self::FORMIO_SERVICE_CLASS;
  }

  public function getFullName(): string
  {
    if ($this->getServiceGroup()) {
      return $this->getServiceGroup()->getName() . ' - ' . $this->getName();
    }
    return $this->name;
  }

  /**
   * Get the value of maxResponseTime
   *
   * @return  int
   */
  public function getMaxResponseTime()
  {
    return $this->maxResponseTime;
  }

  /**
   * Set the value of maxResponseTime
   *
   * @param  int  $maxResponseTime
   *
   * @return  self
   */
  public function setMaxResponseTime($maxResponseTime)
  {
    $this->maxResponseTime = $maxResponseTime;

    return $this;
  }


  /**
   * Get the value of howToDo
   *
   * @return  string
   */
  public function getHowToDo()
  {
    if ($this->serviceGroup != null && $this->sharedWithGroup) {
      return $this->serviceGroup->getHowtoDo();
    }
    return $this->howToDo;
  }

  /**
   * Set the value of howToDo
   *
   * @param string|null $howToDo
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
    if ($this->serviceGroup != null && $this->sharedWithGroup) {
      return $this->serviceGroup->getWhatYouNeed();
    }
    return $this->whatYouNeed;
  }

  /**
   * Set the value of whatYouNeed
   *
   * @param string|null $whatYouNeed
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
    if ($this->serviceGroup != null && $this->sharedWithGroup) {
      return $this->serviceGroup->getWhatYouGet();
    }
    return $this->whatYouGet;
  }

  /**
   * Set the value of whatYouGet
   *
   * @param string|null $whatYouGet
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
    if ($this->serviceGroup != null && $this->sharedWithGroup) {
      return $this->serviceGroup->getCosts();
    }
    return $this->costs;
  }

  /**
   * Set the value of costs
   *
   * @param string|null $costs
   *
   * @return  self
   */
  public function setCosts(?string $costs)
  {
    $this->costs = $costs;

    return $this;
  }

  /**
   * @return ServiceSource|null
   */
  public function getSource(): ?ServiceSource
  {
    if (is_array($this->source)) {
      return new ServiceSource($this->source);
    }
    return $this->source;
  }

  /**
   * @param ServiceSource|null $source
   * @return $this
   */
  public function setSource(?ServiceSource $source): Servizio
  {
    $this->source = $source;
    return $this;
  }

  /**
   * @return ArrayCollection
   */
  public function getCostsAttachments(): ArrayCollection
  {
    if ($this->serviceGroup != null && $this->sharedWithGroup) {
      return $this->serviceGroup->getCostsAttachments();
    }
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
  public function setCostsAttachments(ArrayCollection $costsAttachments): Servizio
  {
    $this->costsAttachments = $costsAttachments;
    return $this;
  }

  /**
   * @param PublicFile $attachment
   *
   * @return $this
   */
  public function addCostsAttachment(PublicFile $attachment): Servizio
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
  public function removeCostsAttachment(PublicFile $attachment): Servizio
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
    return $this->lifeEvents ?? [];
  }

  /**
   * @param array $lifeEvents
   * @return $this
   */
  public function setLifeEvents(array $lifeEvents = []): Servizio
  {
    $this->lifeEvents = $lifeEvents;
    return $this;
  }

  /**
   * @return array
   */
  public function getBusinessEvents()
  {
    return $this->businessEvents ?? [];
  }

  /**
   * @param array $businessEvents
   * @return $this
   */
  public function setBusinessEvents(array $businessEvents = []): Servizio
  {
    $this->businessEvents = $businessEvents;
    return $this;
  }

  public function isActive(): bool
  {
    if (!in_array($this->status, [self::STATUS_AVAILABLE, self::STATUS_PRIVATE, self::STATUS_SCHEDULED])) {
      return false;
    }
    $now = new \DateTime();
    if ($this->status === self::STATUS_SCHEDULED && ($now < $this->scheduledFrom || $now > $this->scheduledTo)) {
      return false;
    }
    return true;
  }

  /**
   * @ORM\PreFlush()
   */
  public function toArray()
  {
    $this->conditionsAttachments = $this->getConditionsAttachments()->toArray();
    $this->costsAttachments = $this->getCostsAttachments()->toArray();

    if ($this->source instanceof ServiceSource) {
      $this->source = $this->source->jsonSerialize();
    }

  }

  /**
   * @ORM\PostLoad()
   * @ORM\PostUpdate()
   */
  public function fromArray()
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

    if ($this->source && !$this->source instanceof ServiceSource) {
      $serviceSource = new ServiceSource();
      $serviceSource->setId($this->source['id']);
      $serviceSource->setUrl($this->source['url']);
      $serviceSource->setMd5($this->source['md5']);
      $serviceSource->setVersion($this->source['version']);
      $serviceSource->setUpdatedAt(new \DateTime($this->source['updatedAt']));
      $serviceSource->setIdentifier($this->source['version'] ?? null);

      $this->source = $serviceSource;
    }
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
   * @return string|null
   */
  public function getIdentifier(): ?string
  {
    return $this->identifier;
  }

  /**
   * @param string|null $identifier
   * @return Servizio
   */
  public function setIdentifier(?string $identifier): Servizio
  {
    $this->identifier = $identifier;
    return $this;
  }

  public function isIdentifierImported(): bool
  {
    if ($this->source && $this->source->getIdentifier()) {
      return true;
    }
    return false;
  }

}
