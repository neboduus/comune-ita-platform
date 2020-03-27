<?php

namespace AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use AppBundle\Model\PaymentParameters;
use AppBundle\Model\FlowStep;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use JMS\Serializer\Annotation as Serializer;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Nelmio\ApiDocBundle\Annotation\Model;
use Swagger\Annotations as SWG;
use JMS\Serializer\Annotation\AccessorOrder;


/**
 * @ORM\Entity
 * @ORM\Table(name="servizio")
 * @ORM\HasLifecycleCallbacks
 */
class Servizio
{

  const STATUS_CANCELLED = 0;
  const STATUS_AVAILABLE = 1;
  const STATUS_SUSPENDED = 2;


  const ACCESS_LEVEL_ANONYMOUS = 0;
  const ACCESS_LEVEL_SOCIAL = 1000;
  const ACCESS_LEVEL_SPID_L1 = 2000;
  const ACCESS_LEVEL_SPID_L2 = 3000;
  const ACCESS_LEVEL_CIE = 4000;


  /**
   * @ORM\Column(type="guid")
   * @ORM\Id
   * @SWG\Property(description="Service's uuid")
   */
  protected $id;

  /**
   * @var string
   *
   * @ORM\Column(type="string", length=100, unique=true)
   * @Assert\NotBlank(message="name")
   * @Assert\NotNull()
   * @SWG\Property(description="Service's name")
   */
  private $name;

  /**
   * @var string
   *
   * @Gedmo\Slug(fields={"name"})
   * @ORM\Column(type="string", length=100)
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
   * @ORM\Column(type="text", nullable=true)
   * @SWG\Property(description="Service's description, accepts html tags")
   */
  private $description;

  /**
   * @var string
   * @ORM\Column(type="text", nullable=true)
   * @SWG\Property(description="Compilation guide, accepts html tags")
   */
  private $howto;

  /**
   * @var string
   * @ORM\Column(type="text", nullable=true)
   * @SWG\Property(description="Textual description of whom the service is addressed, accepts html tags")
   */
  private $who;

  /**
   * @var string
   * @ORM\Column(type="text", nullable=true)
   * @SWG\Property(description="Textual description of any special cases for obtaining the service, accepts html tags")
   */
  private $specialCases;

  /**
   * @var string
   * @ORM\Column(type="text", nullable=true)
   * @SWG\Property(description="Other info, accepts html tags")
   */
  private $moreInfo;

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
   * @var bool
   * @ORM\Column(type="boolean", nullable=true)
   * @SWG\Property(description="Set true if a payment is required")
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
   * @ORM\Column(type="boolean", nullable=true, options={"default":"1"})
   * @SWG\Property(description="If selected the application will be registered in tenants protocol")
   */
  private $protocolRequired;

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
    //$this->paymentParameters = new ArrayCollection();
    $this->status = self::STATUS_AVAILABLE;
    $this->accessLevel = self::ACCESS_LEVEL_SPID_L2;
    $this->setProtocolRequired(true);
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

  /**
   * @return bool
   */
  public function isPaymentRequired()
  {
    return $this->paymentRequired;
  }

  /**
   * @param bool $paymentRequired
   * @return $this;
   */
  public function setPaymentRequired(bool $paymentRequired)
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
  public function setProtocolloParameters(array $protocolloParameters): Servizio
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
  public function getCoverage()
  {
    return $this->coverage;
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
          $flowSteps[] = json_decode($v, true);
        } else {
          $flowSteps[] = $v;
        }
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
        if ($f['type'] == 'formio' && $f['parameters']['formio_id'] && !empty($f['parameters']['formio_id'])) {
          $formID = $f['parameters']['formio_id'];
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
  public function isProtocolRequired(): bool
  {
    return $this->protocolRequired;
  }

  /**
   * @param bool $protocolRequired
   */
  public function setProtocolRequired(bool $protocolRequired)
  {
    $this->protocolRequired = $protocolRequired;
  }


}
