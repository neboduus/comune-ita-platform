<?php

namespace AppBundle\Entity;

use AppBundle\Model\FeedbackMessage;
use AppBundle\Model\FeedbackMessagesSettings;
use AppBundle\Model\IOServiceParameters;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use AppBundle\Model\PaymentParameters;
use AppBundle\Model\FlowStep;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Translatable\Translatable;
use JMS\Serializer\Annotation as Serializer;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Nelmio\ApiDocBundle\Annotation\Model;
use Swagger\Annotations as SWG;

/**
 * @ORM\Entity(repositoryClass="AppBundle\Entity\ServizioRepository")
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

  /**
   * @ORM\Column(type="guid")
   * @ORM\Id
   * @SWG\Property(description="Service's uuid")
   */
  protected $id;

  /**
   * @var string
   * @Gedmo\Translatable
   * @ORM\Column(type="string", length=255, unique=true)
   * @Assert\NotBlank(message="name")
   * @Assert\NotNull()
   * @SWG\Property(description="Service's name")
   */
  private $name;

  /**
   * @var string
   *
   * @Gedmo\Slug(fields={"name"})
   * @ORM\Column(type="string", length=255)
   * @SWG\Property(description="Human-readable unique identifier, if empty will be generated from service's name")
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
   * @ORM\ManyToOne(targetEntity="Categoria")
   * @ORM\JoinColumn(name="topics", referencedColumnName="id", nullable=true)
   * @Serializer\Exclude()
   */
  private $topics;

  /**
   * @var string
   * @Gedmo\Translatable
   * @ORM\Column(type="text", nullable=true)
   * @SWG\Property(description="Service's description, accepts html tags")
   */
  private $description;

  /**
   * @var string
   * @Gedmo\Translatable
   * @ORM\Column(type="text", nullable=true)
   * @SWG\Property(description="Compilation guide, accepts html tags")
   */
  private $howto;

  /**
   * @var string
   * @Gedmo\Translatable
   * @ORM\Column(type="text", nullable=true)
   * @SWG\Property(description="Textual description of whom the service is addressed, accepts html tags")
   */
  private $who;

  /**
   * @var string
   * @Gedmo\Translatable
   * @ORM\Column(type="text", nullable=true)
   * @SWG\Property(description="Textual description of any special cases for obtaining the service, accepts html tags")
   */
  private $specialCases;

  /**
   * @var string
   * @Gedmo\Translatable
   * @ORM\Column(type="text", nullable=true)
   * @SWG\Property(description="Other info, accepts html tags")
   */
  private $moreInfo;

  /**
   * @var string
   * @Gedmo\Translatable
   * @ORM\Column(type="text", nullable=true)
   * @SWG\Property(description="Information shown to the citizen during the compilation of the service, accepts html tags")
   */
  private $compilationInfo;

  /**
   * @var string
   * @Gedmo\Translatable
   * @ORM\Column(type="text", nullable=true)
   * @SWG\Property(description="Indications shown to the citizen at the end of the compilation of the service, accepts html tags")
   */
  private $finalIndications;

  /**
   * @var string[]
   * @ORM\Column(type="array", nullable=true)
   * @SWG\Property(description="Geographical area covered by service", type="array", @SWG\Items(type="string"))
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
   * @ORM\Column(type="json_array", nullable=true)
   * @Serializer\Exclude()
   */
  private $additionalData;

  /**
   * @var FlowStep[]
   * @ORM\Column(type="json_array", nullable=true)
   * @SWG\Property(property="flow_steps", type="array", @SWG\Items(ref=@Model(type=FlowStep::class)))
   */
  private $flowSteps;

  /**
   * @var array
   * @ORM\Column(type="json_array", nullable=true)
   * @Serializer\Type("array<string, string>")
   */
  private $protocolloParameters;

  /**
   * @ORM\Column(type="integer", nullable=true, options={"default":"0"})
   * @SWG\Property(description="Accepts values: 0 - Not required, 1 - Required, 2 - Deferred")
   */
  private $paymentRequired;

  /**
   * @var array
   * @ORM\Column(type="json_array", nullable=true)
   * @SWG\Property(property="payment_parameters", description="List of payment gateways available for the service and related parameters", type="object", ref=@Model(type=PaymentParameters::class))
   */
  private $paymentParameters;

  /**
   * @var array
   * @ORM\Column(type="json_array", nullable=true)
   * @Serializer\Exclude()
   */
  private $integrations;

  /**
   * @var string
   * @ORM\Column(type="string", nullable=true)
   * @Serializer\Exclude()
   * @SWG\Property(description="Accepts html characters")
   *
   */
  protected $handler;

  /**
   * @var bool
   * @ORM\Column(type="boolean", nullable=true)
   * @SWG\Property(description="If selected the service will be shown at the top of the page")
   */
  private $sticky;

  /**
   * @ORM\Column(type="integer")
   * @Assert\NotBlank(message="identifier")
   * @Assert\NotNull()
   * @SWG\Property(description="Accepts values: 0 - Hidden, 1 - Pubblished, 2 - Suspended")
   */
  private $status;

  /**
   * @ORM\Column(type="integer", nullable=true)
   * @SWG\Property(description="Accepts values: 0 - Anonymous, 1000 - Social, 2000 - Spid level 1, 3000 - Spid level 2, 4000 - Cie")
   */
  private $accessLevel;

  /**
   * @var bool
   * @ORM\Column(name="login_suggested", type="boolean", nullable=true)
   * @SWG\Property(description="Enable or disable the suggestion to log in to auto-complete some fields")
   */
  private $loginSuggested;

  /**
   * @var bool
   * @ORM\Column(type="boolean", nullable=true, options={"default":"1"})
   * @SWG\Property(description="If selected the application will be registered in tenants protocol")
   */
  private $protocolRequired;

  /**
   * @var string
   * @ORM\Column(type="string", nullable=true)
   * @SWG\Property(description="Service protocol handler: dummy, pec, pitre, infor")
   */
  private $protocolHandler;

  /**
   * @var FeedbackMessage[]
   * @Gedmo\Translatable
   * @ORM\Column(type="json", nullable=true)
   * @SWG\Property(description="Service feedback messages")
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
   * @ORM\ManyToOne(targetEntity="AppBundle\Entity\ServiceGroup", inversedBy="services")
   * @ORM\JoinColumn(name="service_group_id", referencedColumnName="id", nullable=true)
   * @SWG\Property(description="Service group id", type="string")
   */
  private $serviceGroup;

  /**
   * @var bool
   * @ORM\Column(type="boolean", nullable=true, options={"default":"0"})
   * @SWG\Property(description="If selected the service will share the group's descriptipn")
   */
  private $sharedWithGroup;

  /**
   * @var bool
   * @ORM\Column(type="boolean", nullable=true, options={"default":"0"})
   * @SWG\Property(description="If selected, service's applications can be reopend")
   */
  private $allowReopening;

  /**
   * @var bool
   * @ORM\Column(type="boolean", nullable=true, options={"default":"1"})
   * @SWG\Property(description="If selected, service's applications can be withdraw")
   */
  private $allowWithdraw;

  /**
   * @var integer
   * @ORM\Column(type="integer", nullable=true, options={"default":"0"})
   * @SWG\Property(description="Service workflow type, accepts values: 0 - Approval, 1 - Forward")
   */
  private $workflow;

  /**
   * @var array
   * @ORM\Column(name="io_service_parameters", type="json", nullable=true)
   * @SWG\Property(property="io_service_parameters", description="List of parameters required for integration with the io app", type="object", ref=@Model(type=IOServiceParameters::class))
   */
  private $IOServiceParameters;

  /**
   * @var string
   * @ORM\Column(type="string", nullable=true)
   */
  private $backofficeFormId;

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

    $this->enti = new ArrayCollection();
    $this->erogatori = new ArrayCollection();
    $this->flowSteps = new ArrayCollection();
    $this->feedbackMessages = new ArrayCollection();
    //$this->paymentParameters = new ArrayCollection();
    $this->status = self::STATUS_AVAILABLE;
    $this->accessLevel = self::ACCESS_LEVEL_SPID_L2;
    $this->setLoginSuggested(false);
    //$this->setProtocolRequired(true);
    $this->setAllowReopening(true);
    $this->setAllowWithdraw(true);
    $this->setFinalIndications('La domanda è stata correttamente registrata, non ti sono richieste altre operazioni. Grazie per la tua collaborazione.');
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
  public function getHowto()
  {
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
   * @SWG\Property(description="Service's response type")
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
  public function isSticky()
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
   */
  public function getTopicsId()
  {
    return $this->topics->getId();
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
  public function setWho( $who )
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
  public function setSpecialCases( $specialCases )
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
  public function setMoreInfo( $moreInfo )
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
   * @return array
   */
  public function getFlowSteps()
  {
    $flowSteps = [];
    if ( count($this->flowSteps) > 0) {
      /*return array_map(function ($flowSteps) {
        $flowStep = json_decode($flowSteps);
        return new FlowStep($flowStep->identifier, $flowStep->title, $flowStep->type, $flowStep->description, $flowStep->guide, $flowStep->parameters);
      }, $this->flowSteps->toArray());*/
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
  public function getFeedbackMessages(): ?array
  {
    return $this->feedbackMessages;
  }

  /**
   * @param FeedbackMessage[] $feedbackMessages
   */
  public function setFeedbackMessages(array $feedbackMessages)
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
   * @param \DateTime $scheduledFrom
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
    if (!$parameters || !($parameters['IOserviceId'] && $parameters['primaryKey'] && $parameters['secondaryKey'])) return false;

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
  
  public function setTranslatableLocale($locale)
  {
    $this->locale = $locale;
  }

}
