<?php


namespace App\Dto;

use App\Entity\Categoria;
use App\Entity\Recipient;
use App\Entity\ServiceGroup;
use App\Entity\Servizio;
use App\Model\PaymentParameters;
use App\Model\FlowStep;
use App\Model\IOServiceParameters;
use App\Model\ServiceSource;
use App\Services\Manager\BackofficeManager;
use Doctrine\Common\Collections\ArrayCollection;
use JMS\Serializer\Annotation as Serializer;
use JMS\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Annotations as OA;
use Gedmo\Mapping\Annotation as Gedmo;

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
   * @Serializer\Type("array")
   * @OA\Property(property="source", description="Source of the service if imported", type="object", ref=@Model(type=ServiceSource::class))
   * @Groups({"read"})
   */
  private $source;

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
   * @param string $howto
   */
  public function setHowto(string $howto)
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
  public function setSticky(bool $sticky)
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
  public function setLoginSuggested( $loginSuggested )
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
  public function setSharedWithGroup( $shared )
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
  public function setAllowReopening(bool $allowReopening)
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
  public function setWorkflow(int $workflow)
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
   * @param  integer  $maxResponseTime
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
   * @param ServiceSource|null $source
   *
   * @return self
   */
  public function setSource(?ServiceSource $source)
  {
    $this->source = $source;
    return $this;
  }


  /**
   * @param Servizio $servizio
   * @return Service
   */
  public static function fromEntity(Servizio $servizio, $formServerUrl)
  {
    $dto = new self();
    $dto->id = $servizio->getId();
    $dto->name = $servizio->getName();
    $dto->slug = $servizio->getSlug();
    $dto->tenant = $servizio->getEnteId();

    $dto->topics = $servizio->getTopics() ? $servizio->getTopics()->getSlug() : null;
    $dto->topics_id = $servizio->getTopics() ? $servizio->getTopics()->getId() : null;

    $dto->description = $servizio->getDescription() ?? '';
    $dto->shortDescription = $servizio->getShortDescription() ?? '';
    $dto->howto = $servizio->getHowto();
    $dto->who = $servizio->getWho() ?? '';
    $dto->specialCases = $servizio->getSpecialCases() ?? '';
    $dto->moreInfo = $servizio->getMoreInfo() ?? '';
    $dto->coverage = $servizio->getCoverage();

    $dto->compilationInfo = $servizio->getCompilationInfo() ?? '';
    $dto->finalIndications = $servizio->getFinalIndications() ?? '';
    $dto->flowSteps = self::prepareFlowSteps($servizio->getFlowSteps(), $formServerUrl);
    $dto->setProtocolRequired($servizio->isProtocolRequired());
    $dto->protocolloParameters = [];
    $dto->paymentRequired = $servizio->getPaymentRequired();
    $dto->protocolHandler = $servizio->getProtocolHandler();
    $dto->paymentParameters = [];
    $dto->integrations = self::decorateIntegrationsData($servizio->getIntegrations());
    $dto->sticky = $servizio->isSticky();
    $dto->status = $servizio->getStatus();
    $dto->accessLevel = $servizio->getAccessLevel();
    $dto->loginSuggested = $servizio->isLoginSuggested() || false;
    $dto->scheduledFrom = $servizio->getScheduledFrom();
    $dto->scheduledTo = $servizio->getScheduledTo();
    $dto->serviceGroupId = $servizio->getServiceGroup() ? $servizio->getServiceGroup()->getId() : null;
    $dto->serviceGroup = $servizio->getServiceGroup() ? $servizio->getServiceGroup()->getSlug() : null;
    $dto->sharedWithGroup = $servizio->isSharedWithGroup();
    $dto->allowReopening = $servizio->isAllowReopening();
    $dto->allowWithdraw = $servizio->isAllowWithdraw();
    $dto->allowIntegrationRequest = $servizio->isAllowIntegrationRequest();
    $dto->workflow = $servizio->getWorkflow();
    $dto->maxResponseTime = $servizio->getMaxResponseTime();
    $dto->howToDo = $servizio->getHowToDo();
    $dto->whatYouNeed = $servizio->getWhatYouNeed();
    $dto->whatYouGet = $servizio->getWhatYouGet();
    $dto->costs = $servizio->getCosts();

    $dto->recipients = [];
    $dto->recipientsId = [];

    if ($servizio->getRecipients()) {
      foreach ($servizio->getRecipients() as $r) {
        $dto->recipients[]= $r->getName();
        $dto->recipientsId[]= $r->getId();
      }
    }

    $dto->geographicAreas = [];
    $dto->geographicAreasId = [];

    if ($servizio->getGeographicAreas()) {
      foreach ($servizio->getGeographicAreas() as $g) {
        $dto->geographicAreas[]= $g->getName();
        $dto->geographicAreasId[]= $g->getId();
      }
    }

    $dto->source = $servizio->getSource();

    return $dto;
  }

  /**
   * @param Servizio|null $entity
   * @return Servizio
   */
  public function toEntity(Servizio $entity = null)
  {
    if (!$entity) {
      $entity = new Servizio();
    }

    $entity->setName($this->name);
    $entity->setSlug($this->slug);

    // Avoid validation error on patch
    if ($this->topics instanceof Categoria) {
      $entity->setTopics($this->topics);
    }

    $entity->setDescription($this->description ?? '');
    $entity->setShortDescription($this->shortDescription ?? '');
    $entity->setHowto($this->howto);
    $entity->setWho($this->who ?? '');
    $entity->setSpecialCases($this->specialCases ?? '');
    $entity->setMoreInfo($this->moreInfo ?? '');
    $entity->setCompilationInfo($this->compilationInfo ?? '');
    $entity->setFinalIndications($this->finalIndications ?? '');
    $entity->setCoverage(implode(',', (array)$this->coverage)); //@TODO
    $entity->setFlowSteps($this->flowSteps);
    $entity->setProtocolRequired($this->isProtocolRequired());
    $entity->setProtocolHandler($this->getProtocolHandler());
    $entity->setProtocolloParameters($this->protocolloParameters);
    $entity->setPaymentRequired($this->paymentRequired);
    $entity->setPaymentParameters($this->paymentParameters);
    $entity->setIntegrations(self::normalizeIntegrationsData($this->integrations));
    $entity->setSticky($this->sticky);
    $entity->setStatus($this->status);
    $entity->setAccessLevel($this->getAccessLevel());
    $entity->setLoginSuggested($this->loginSuggested);
    $entity->setScheduledFrom($this->scheduledFrom);
    $entity->setScheduledTo($this->scheduledTo);
    $entity->setIOServiceParameters($this->ioParameters);

    // Avoid validation error on patch
    if ($this->serviceGroup instanceof ServiceGroup) {
      $entity->setServiceGroup($this->serviceGroup);
    }
    $entity->setSharedWithGroup($this->sharedWithGroup);

    $entity->setAllowReopening($this->allowReopening);
    $entity->setAllowWithdraw($this->allowWithdraw);
    $entity->setAllowIntegrationRequest($this->allowIntegrationRequest);
    $entity->setWorkflow($this->workflow);
    $entity->setMaxResponseTime($this->maxResponseTime);
    $entity->setHowToDo($this->howToDo);
    $entity->setWhatYouNeed($this->whatYouNeed);
    $entity->setWhatYouGet($this->whatYouGet);
    $entity->setCosts($this->costs);

    $entity->setRecipients(new ArrayCollection($this->recipientsId));
    $entity->setGeographicAreas(new ArrayCollection($this->geographicAreasId));
    $entity->setSource($this->source);

    return $entity;
  }

  /**
   * @param $data
   * @return mixed
   */
  public static function normalizeData($data)
  {
    // Todo: find better way
    if (isset($data['flow_steps']) && count($data['flow_steps']) > 0) {
      $temp = [];
      foreach ($data['flow_steps'] as $f) {
        $f['parameters'] = \json_encode($f['parameters']);
        $temp[]= $f;
      }
      $data['flow_steps'] = $temp;
    }

    // Todo: find better way
    if ( isset($data['payment_parameters']['gateways']) && count($data['payment_parameters']['gateways']) > 0 ) {
      $sanitizedGateways = [];
      foreach ($data['payment_parameters']['gateways'] as $gateway) {
        $parameters = \json_encode($gateway['parameters']);
        $gateway['parameters'] = $parameters;
        $sanitizedGateways [$gateway['identifier']]= $gateway;
      }
      $data['payment_parameters']['gateways'] = $sanitizedGateways;
    }

    // Todo: find better way
    if (isset($data['protocollo_parameters'])) {
      $data['protocollo_parameters'] = \json_encode($data['protocollo_parameters']);
    }
    return $data;
  }


  public static function prepareFlowSteps( $flowSteps, $formServerUrl ) {
    if (empty($flowSteps)) {
      return $flowSteps;
    }
    foreach ($flowSteps as $flowStep) {
      $flowStep->addParameter("url", $formServerUrl . '/form/');
    }
    return $flowSteps;
  }

  public static function decorateIntegrationsData($integrations) {
    $data = [];
    if (empty($integrations)) {
      return $data;
    }

    foreach ($integrations as $status => $className) {
      $data["trigger"] = $status;
      $data["action"] = (new \ReflectionClass($className))->getConstant("IDENTIFIER");
    }
    return $data;
  }

  public static function normalizeIntegrationsData($integrations) {
    if (isset($integrations['trigger']) && $integrations['trigger']) {
      return [$integrations['trigger'] => BackofficeManager::getBackofficeClassByIdentifier($integrations['action'])];
    } else {
      return null;
    }
  }

}
