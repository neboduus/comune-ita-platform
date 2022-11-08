<?php


namespace App\Model;

use App\Entity\Servizio;
use Gedmo\Mapping\Annotation as Gedmo;
use JMS\Serializer\Annotation as Serializer;
use JMS\Serializer\Annotation\Groups;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Annotations as OA;
use Symfony\Component\Validator\Constraints as Assert;

class Service
{

  /**
   * @Serializer\Type("string")
   * @OA\Property(description="Service's uuid")
   * @Groups({"read"})
   */
  protected $id;

  /**
   * @var string
   *
   * @Assert\NotBlank(message="This field is mandatory: name")
   * @Assert\NotNull(message="This field is mandatory: name")
   * @Serializer\Type("string")
   * @OA\Property(description="Service's name")
   * @Groups({"read", "write"})
   * @Assert\Length(max="255")
   */
  private $name;

  /**
   * @var string
   *
   * @Gedmo\Slug(fields={"name"})
   * @Serializer\Type("string")
   * @OA\Property(description="Human-readable unique identifier, if empty will be generated from service's name")
   * @Groups({"read"})
   */
  private $slug;

  /**
   * @Serializer\Type("string")
   * @OA\Property(description="Services's tenant id")
   * @Groups({"read", "write"})
   */
  private $tenant;

  /**
   *
   * @Serializer\Type("string")
   * @OA\Property(description="Services's topic slug")
   * @Groups({"read", "write"})
   */
  private $topics;

  /**
   *
   * @Serializer\Type("string")
   * @OA\Property(description="Services's topic id")
   * @Groups({"read"})
   */
  private $topics_id;

  /**
   * @var string
   * @Serializer\Type("string")
   * @OA\Property(description="Service's description, accepts html tags")
   * @Groups({"read", "write"})
   */
  private $description;

  /**
   * @var string
   * @Serializer\Type("string")
   * @OA\Property(description="Service's summary")
   * @Groups({"read", "write"})
   * @Assert\Length(max="160")
   */
  private $shortDescription;

  /**
   * @var string
   * @Serializer\Type("string")
   * @OA\Property(description="Compilation guide, accepts html tags")
   * @Groups({"read", "write"})
   */
  private $howto;

  /**
   * @var string
   * @Serializer\Type("string")
   * @OA\Property(description="How to fill in the application")
   * @Groups({"read", "write"})
   */
  private $howToDo;

  /**
   * @var string
   * @Serializer\Type("string")
   * @OA\Property(description="What you need to fill in the application")
   * @Groups({"read", "write"})
   */
  private $whatYouNeed;

  /**
   * @var string
   * @Serializer\Type("string")
   * @OA\Property(description="The outcome of the application")
   * @Groups({"read", "write"})
   */
  private $whatYouGet;

  /**
   * @var string
   * @Serializer\Type("string")
   * @OA\Property(description="Costs of this application")
   * @Groups({"read", "write"})
   */
  private $costs;

  /**
   * @var PublicFile[]
   * @OA\Property(property="costs_attachments", description="Costs attachments list", type="array", @OA\Items(type="object"))
   * @Serializer\Type("array")
   * @Groups({"read"})
   */
  private $costsAttachments;

  /**
   * @var string
   * @Serializer\Type("string")
   * @OA\Property(description="Textual description of whom the service is addressed, accepts html tags")
   * @Groups({"read", "write"})
   */
  private $who;

  /**
   * @var string
   * @Serializer\Type("string")
   * @OA\Property(description="Textual description of any special cases for obtaining the service, accepts html tags")
   * @Groups({"read", "write"})
   */
  private $specialCases;

  /**
   * @var string
   * @Serializer\Type("string")
   * @OA\Property(description="Other info, accepts html tags")
   * @Groups({"read", "write"})
   */
  private $moreInfo;

  /**
   * @var string
   * @Serializer\Type("string")
   * @OA\Property(description="Any restrictions on access to the service, accepts html tags")
   * @Groups({"read", "write"})
   */
  private $constraints;

  /**
   * @var string
   * @Serializer\Type("string")
   * @OA\Property(description="Service times and deadlines, accepts html tags")
   * @Groups({"read", "write"})
   */
  private $timesAndDeadlines;

  /**
   * @var string
   * @Serializer\Type("string")
   * @OA\Property(description="Call to action for booking an appointment", type="string", example="https://www.example.com/booking")
   * @Groups({"read", "write"})
   */
  private $bookingCallToAction;

  /**
   * @var string
   * @Serializer\Type("string")
   * @OA\Property(description="Service conditions, accepts html tags")
   * @Groups({"read", "write"})
   */
  private $conditions;

  /**
   * @var PublicFile[]
   * @OA\Property(property="conditions_attachments", description="Conditions attachments list", type="array", @OA\Items(type="object"))
   * @Serializer\Type("array")
   * @Groups({"read"})
   */
  private $conditionsAttachments;

  /**
   * @var string
   * @Serializer\Type("string")
   * @OA\Property(description="Information shown to the citizen during the compilation of the service, accepts html tags")
   * @Groups({"read", "write"})
   */
  private $compilationInfo;

  /**
   * @var string
   * @Serializer\Type("string")
   * @OA\Property(description="Indications shown to the citizen at the end of the compilation of the service, accepts html tags")
   * @Groups({"read", "write"})
   */
  private $finalIndications;

  /**
   * @var string[]
   * @Serializer\Type("array<string>")
   * @OA\Property(description="Geographical area covered by service", type="array", @OA\Items(type="string"))
   * @Groups({"read", "write"})
   */
  private $coverage;

  /**
   * @var string
   * @Serializer\Type("string")
   * @OA\Property(description="Response type from service, possible values: <br/> standard - Operator can accept or reject the application <br/> attachment - Operator can accept or reject the application and in case of acceptance, attach a response file <br/> signed_attachment - Operator can accept or reject the application and in case of acceptance, attach a signed response file")
   * @Groups({"read", "write"})
   */
  private $response_type;

  /**
   * @var FlowStep[]
   * @Assert\NotBlank(message="You have to specify at least one step: flow_steps")
   * @Assert\NotNull(message="You have to specify at least one step: flow_steps")
   * @OA\Property(property="flow_steps", type="array", @OA\Items(ref=@Model(type=FlowStep::class)))
   * @Serializer\Type("array")
   * @Groups({"read", "write"})
   */
  private $flowSteps;

  /**
   * @var bool
   * @Serializer\Type("boolean")
   * @OA\Property(description="Set true if a protocol is required")
   * @Groups({"read", "write"})
   */
  private $protocolRequired;

  /**
   * @var string
   * @Serializer\Type("string")
   * @OA\Property(description="Protocol handler type")
   * @Groups({"read", "write"})
   */
  private $protocolHandler;

  /**
   * @var array
   * @OA\Property(property="protocollo_parameters", description="Service's parameters for tenant's register"))
   * @Serializer\Type("array<string, string>")
   * @Groups({"read", "write"})
   */
  private $protocolloParameters;

  /**
   * @var integer
   * @Serializer\Type("integer")
   * @OA\Property(description="Accepts values: 0 - Not Rquired, 1 - Immediate, 2 - Referred")
   * @Groups({"read", "write"})
   */
  private $paymentRequired;

  /**
   * @var array
   * @OA\Property(property="payment_parameters", description="List of payment gateways available for the service and related parameters", type="object", ref=@Model(type=PaymentParameters::class))
   * @Serializer\Type("array")
   * @Groups({"read", "write"})
   */
  private $paymentParameters;

  /**
   * @var array
   * @OA\Property(property="integrations", description="Service's backoffice integration")
   * @Serializer\Type("array")
   * @Groups({"read", "write"})
   */
  private $integrations;


  /**
   * @var bool
   * @Serializer\Type("boolean")
   * @OA\Property(description="If selected the service will be shown at the top of the page")
   * @Groups({"read", "write"})
   */
  private $sticky;

  /**
   * @Assert\NotBlank(message="This field is mandatory: name")
   * @Assert\NotNull(message="This field is mandatory: name")
   * @Serializer\Type("integer")
   * @OA\Property(description="Accepts values: 0 - Hidden, 1 - Pubblished, 2 - Suspended, 3 - private, 4 - scheduled")
   * @Groups({"read", "write"})
   */
  private $status;

  /**
   * @Serializer\Type("integer")
   * @OA\Property(description="Accepts values: 0 - Anonymous, 1000 - Social, 2000 - Spid Level 1, 3000 - Spid Level 2, 4000 - Cie")
   * @Groups({"read", "write"})
   */
  private $accessLevel;

  /**
   * @var bool
   * @OA\Property(description="Enable or disable the suggestion to log in to auto-complete some fields")
   * @Groups({"read", "write"})
   */
  private $loginSuggested;

  /**
   * @Serializer\Type("datetime")
   * @OA\Property(description="Scheduled from date time")
   * @Groups({"read", "write"})
   */
  private $scheduledFrom;

  /**
   * @Serializer\Type("datetime")
   * @OA\Property(description="Scheduled to date time")
   * @Groups({"read", "write"})
   */
  private $scheduledTo;

  /**
   * @Serializer\Type("string")
   * @OA\Property(description="Services groups (slug)")
   * @Groups({"read", "write"})
   */
  private $serviceGroup;

  /**
   * @Serializer\Type("string")
   * @OA\Property(description="Services group id")
   * @Groups({"read", "write"})
   */
  private $serviceGroupId;

  /**
   * @var bool
   * @Serializer\Type("boolean")
   * @OA\Property(description="If selected the service will share the group's descriptipn")
   * @Groups({"read", "write"})
   */
  private $sharedWithGroup;

  /**
   * @var bool
   * @Serializer\Type("boolean")
   * @OA\Property(description="If selected, service's applications can be reopend")
   * @Groups({"read", "write"})
   */
  private $allowReopening;

  /**
   * @var bool
   * @Serializer\Type("boolean")
   * @OA\Property(description="If selected, service's applications can be withdraw")
   * @Groups({"read", "write"})
   */
  private $allowWithdraw;

  /**
   * @var bool
   * @Serializer\Type("boolean")
   * @OA\Property(description="If selected, operator can request integrations for applications on this service")
   * @Groups({"read", "write"})
   */
  private $allowIntegrationRequest;

  /**
   * @var integer
   * @Serializer\Type("integer")
   * @OA\Property(description="If selected, service's applications can be reopend")
   * @Groups({"read", "write"})
   */
  private $workflow;

  /**
   * @var array
   * @OA\Property(property="io_parameters", description="Io parameters", type="object", ref=@Model(type=IOServiceParameters::class))
   * @Serializer\Exclude()
   */
  private $ioParameters;

  /**
   * @var string[]
   * @Serializer\Type("array<string>")
   * @OA\Property(description="Service's recipients name", type="array", @OA\Items(type="string"))
   * @Groups({"read"})
   */
  private $recipients;

  /**
   * @var string[]
   * @Serializer\Type("array<string>")
   * @OA\Property(description="Service's recipients id", type="array", @OA\Items(type="string"))
   * @Groups({"read", "write"})
   */
  private $recipientsId;

  /**
   * @var string[]
   * @Serializer\Type("array<string>")
   * @OA\Property(description="Service's geographic areas name", type="array", @OA\Items(type="string"))
   * @Groups({"read"})
   */
  private $geographicAreas;

  /**
   * @var string[]
   * @Serializer\Type("array<string>")
   * @OA\Property(description="Service's geographic areas id", type="array", @OA\Items(type="string"))
   * @Groups({"read", "write"})
   */
  private $geographicAreasId;

  /**
   * @var integer
   * @Serializer\Type("integer")
   * @OA\Property(description="Maximum service delivery time in days. The service will be answered within <maxResponseTime> days.")
   * @Groups({"read", "write"})
   */
  private $maxResponseTime;

  /**
   * @var ServiceSource
   * @OA\Property(property="source", description="Source of the service if imported", type="array", ref=@Model(type=ServiceSource::class))
   * @Groups({"read"})
   */
  private $source;

  /**
   * @var array
   * @Serializer\Type("array<string>")
   * @OA\Property(
   *   description="Linked life events from https://ontopia-lodview.agid.gov.it/controlled-vocabulary/classifications-for-public-services/life-business-event/life-event",
   *   type="array", @OA\Items(type="string", example="https://ontopia-lodview.agid.gov.it/controlled-vocabulary/classifications-for-public-services/life-business-event/life-event/1"))
   * @Groups({"read", "write"})
   */
  private $lifeEvents;

  /**
   * @var array
   * @Serializer\Type("array<string>")
   * @OA\Property(description="Linked business events from https://ontopia-lodview.agid.gov.it/controlled-vocabulary/classifications-for-public-services/life-business-event/business-event",
   *   type="array", @OA\Items(type="string", example="https://ontopia-lodview.agid.gov.it/controlled-vocabulary/classifications-for-public-services/life-business-event/business-event/1"))
   * @Groups({"read", "write"})
   */
  private $businessEvents;

  /**
   * @Serializer\Type("string")
   * @OA\Property(description="External service card url")
   * @Groups({"read", "write"})
   */
  private $externalCardUrl;


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
  public function getName()
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
  public function getSlug()
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
   * @return mixed
   */
  public function getTenant()
  {
    return $this->tenant;
  }

  /**
   * @param mixed $tenant
   */
  public function setTenant($tenant)
  {
    $this->tenant = $tenant;
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
   */
  public function setTopics($topics)
  {
    $this->topics = $topics;
  }

  /**
   * @return mixed
   */
  public function getTopicsId()
  {
    return $this->topics_id;
  }

  /**
   * @param mixed $topics_id
   */
  public function setTopicsId($topics_id): void
  {
    $this->topics_id = $topics_id;
  }

  /**
   * @return string
   */
  public function getDescription()
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
   * @param string|null $howto
   */
  public function setHowto(?string $howto)
  {
    $this->howto = $howto;
  }

  /**
   * @return string
   */
  public function getWho()
  {
    return $this->who;
  }

  /**
   * @param string $who
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
   * @param PublicFile[] $conditionsAttachments
   */
  public function setConditionsAttachments(array $conditionsAttachments)
  {
    $this->conditionsAttachments = $conditionsAttachments;
  }

  /**
   * @return PublicFile[]
   */
  public function getConditionsAttachments(): array
  {
    return $this->conditionsAttachments;
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
  public function setCompilationInfo($compilationInfo)
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
  public function setFinalIndications($finalIndications)
  {
    $this->finalIndications = $finalIndications;
  }

  /**
   * @return string[]
   */
  public function getCoverage()
  {
    return $this->coverage;
  }

  /**
   * @param string[] $coverage
   */
  public function setCoverage(array $coverage)
  {
    $this->coverage = $coverage;
  }

  /**
   * @return string
   */
  public function getResponseType()
  {
    return $this->response_type;
  }

  /**
   * @param string $response_type
   */
  public function setResponseType(string $response_type)
  {
    $this->response_type = $response_type;
  }


  /**
   * @return FlowStep[]
   */
  public function getFlowSteps()
  {
    return $this->flowSteps;
  }

  /**
   * @param FlowStep[] $flowSteps
   */
  public function setFlowSteps(array $flowSteps)
  {
    $this->flowSteps = $flowSteps;
  }

  /**
   * @return bool
   */
  public function isProtocolRequired()
  {
    return $this->protocolRequired;
  }

  /**
   * @param bool $protocolRequired
   */
  public function setProtocolRequired($protocolRequired)
  {
    $this->protocolRequired = $protocolRequired;
  }

  /**
   * @return string
   */
  public function getProtocolHandler()
  {
    return $this->protocolHandler;
  }

  /**
   * @param string $protocolHandler
   */
  public function setProtocolHandler($protocolHandler)
  {
    $this->protocolHandler = $protocolHandler;
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
   */
  public function setProtocolloParameters($protocolloParameters)
  {
    if (!is_array($protocolloParameters)) {
      $protocolloParameters = json_decode($protocolloParameters, true);
    }
    $this->protocolloParameters = $protocolloParameters;
  }

  /**
   * @return bool
   */
  public function isPaymentRequired()
  {
    return $this->paymentRequired === Servizio::PAYMENT_REQUIRED;
  }

  /**
   * @return bool
   */
  public function getPaymentRequired()
  {
    return $this->paymentRequired;
  }

  /**
   * @param $paymentRequired
   */
  public function setPaymentRequired($paymentRequired)
  {
    $this->paymentRequired = $paymentRequired;
  }

  /**
   * @return array
   */
  public function getPaymentParameters()
  {
    return $this->paymentParameters;
  }

  /**
   * @param array $paymentParameters
   */
  public function setPaymentParameters(array $paymentParameters)
  {
    $this->paymentParameters = $paymentParameters;
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
   */
  public function setIntegrations($integrations)
  {
    $this->integrations = $integrations;
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
   */
  public function setSticky(?bool $sticky)
  {
    $this->sticky = $sticky;
  }

  /**
   * @return mixed
   */
  public function getStatus()
  {
    return $this->status;
  }

  /**
   * @param mixed $status
   */
  public function setStatus($status)
  {
    $this->status = $status;
  }

  /**
   * @return mixed
   */
  public function getAccessLevel()
  {
    return $this->accessLevel;
  }

  /**
   * @param mixed $accessLevel
   */
  public function setAccessLevel($accessLevel)
  {
    $this->accessLevel = $accessLevel;
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
  public function setLoginSuggested($loginSuggested)
  {
    $this->loginSuggested = $loginSuggested;
    return $this;
  }

  /**
   * @return mixed
   */
  public function getScheduledFrom()
  {
    return $this->scheduledFrom;
  }

  /**
   * @param mixed $scheduledFrom
   */
  public function setScheduledFrom($scheduledFrom)
  {
    $this->scheduledFrom = $scheduledFrom;
  }

  /**
   * @return mixed
   */
  public function getScheduledTo()
  {
    return $this->scheduledTo;
  }

  /**
   * @param mixed $scheduledTo
   */
  public function setScheduledTo($scheduledTo)
  {
    $this->scheduledTo = $scheduledTo;
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
  public function setServiceGroup($serviceGroup)
  {
    if (!$serviceGroup) {
      $this->sharedWithGroup = false;
    }
    $this->serviceGroup = $serviceGroup;
  }

  /**
   * @return mixed
   */
  public function getServiceGroupId()
  {
    return $this->serviceGroupId;
  }

  /**
   * @param mixed $serviceGroupId
   */
  public function setServiceGroupId($serviceGroupId): void
  {
    $this->serviceGroupId = $serviceGroupId;
  }

  /**
   * @return bool
   */
  public function isSharedWithGroup()
  {
    return $this->sharedWithGroup;
  }

  /**
   * @param bool $sticky
   * @return $this
   */
  public function setSharedWithGroup($shared)
  {
    $this->sharedWithGroup = $shared;
    return $this;
  }

  /**
   * @return bool
   */
  public function isAllowReopening()
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
  public function getWorkflow()
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

  /**
   * @return array
   */
  public function getIoParameters(): ?array
  {
    return $this->ioParameters;
  }

  /**
   * @param array $ioParameters
   */
  public function setIoParameters(?array $ioParameters): void
  {
    $this->ioParameters = $ioParameters;
  }

  /**
   * @return string[]
   */
  public function getRecipients(): array
  {
    return $this->recipients;
  }

  /**
   * @param string[] $recipients
   */
  public function setRecipients(?array $recipients): void
  {
    $this->recipients = $recipients;
  }

  /**
   * @return string[]
   */
  public function getRecipientsId(): ?array
  {
    return $this->recipientsId;
  }

  /**
   * @param string[] $recipientsId
   */
  public function setRecipientsId(?array $recipientsId): void
  {
    $this->recipientsId = $recipientsId;
  }

  /**
   * @return string[]
   */
  public function getGeographicAreas(): ?array
  {
    return $this->geographicAreas;
  }

  /**
   * @param string[] $geographicAreas
   */
  public function setGeographicAreas(?array $geographicAreas): void
  {
    $this->geographicAreas = $geographicAreas;
  }

  /**
   * @return string[]
   */
  public function getGeographicAreasId(): ?array
  {
    return $this->geographicAreasId;
  }

  /**
   * @param string[] $geographicAreasId
   */
  public function setGeographicAreasId(?array $geographicAreasId): void
  {
    $this->geographicAreasId = $geographicAreasId;
  }

  /**
   * Get the value of maxResponseTime
   *
   * @return  integer
   */
  public function getMaxResponseTime()
  {
    return $this->maxResponseTime;
  }

  /**
   * Set the value of maxResponseTime
   *
   * @param integer $maxResponseTime
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
  public function getSource()
  {
    return $this->source;
  }

  /**
   * @param $source
   *
   * @return self
   */
  public function setSource($source)
  {
    $this->source = $source;
    return $this;
  }

  /**
   * @param PublicFile[] $costsAttachments
   */
  public function setCostsAttachments(array $costsAttachments)
  {
    $this->costsAttachments = $costsAttachments;
  }

  /**
   * @return PublicFile[]
   */
  public function getCostsAttachments(): array
  {
    return $this->costsAttachments;
  }

  /**
   * @return array
   */
  public function getLifeEvents(): array
  {
    return $this->lifeEvents ?? [];
  }

  /**
   * @param array $lifeEvents
   * @return $this
   */
  public function setLifeEvents(array $lifeEvents): Service
  {
    $this->lifeEvents = $lifeEvents;
    return $this;
  }

  /**
   * @return array
   */
  public function getBusinessEvents(): array
  {
    return $this->businessEvents ?? [];
  }

  /**
   * @param array $businessEvents
   * @return $this
   */
  public function setBusinessEvents(array $businessEvents): Service
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

}
