<?php


namespace AppBundle\Dto;

use AppBundle\Entity\Categoria;
use AppBundle\Entity\Servizio;
use AppBundle\Model\PaymentParameters;
use AppBundle\Model\FlowStep;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;
use Nelmio\ApiDocBundle\Annotation\Model;
use Swagger\Annotations as SWG;
use Gedmo\Mapping\Annotation as Gedmo;
use JMS\Serializer\Annotation\AccessorOrder;

class Service
{

  /**
   * @Serializer\Type("string")
   * @SWG\Property(description="Service's uuid")
   */
  protected $id;

  /**
   * @var string
   *
   * @Assert\NotBlank(message="This field is mandatory: name")
   * @Assert\NotNull(message="This field is mandatory: name")
   * @Serializer\Type("string")
   * @SWG\Property(description="Service's name")
   */
  private $name;

  /**
   * @var string
   *
   * @Gedmo\Slug(fields={"name"})
   * @Serializer\Type("string")
   * @SWG\Property(description="Human-readable unique identifier, if empty will be generated from service's name")
   */
  private $slug;

  /**
   * @Serializer\Type("string")
   * @SWG\Property(description="Services's tenant (uuid)")
   */
  private $tenant;

  /**
   *
   * @Serializer\Type("string")
   * @SWG\Property(description="Services's topic (slug)")
   */
  private $topics;


  /**
   * @var string
   * @Serializer\Type("string")
   * @SWG\Property(description="Service's description, accepts html tags")
   */
  private $description;

  /**
   * @var string
   * @Serializer\Type("string")
   * @SWG\Property(description="Compilation guide, accepts html tags")
   */
  private $howto;

  /**
   * @var string
   * @Serializer\Type("string")
   * @SWG\Property(description="Textual description of whom the service is addressed, accepts html tags")
   */
  private $who;

  /**
   * @var string
   * @Serializer\Type("string")
   * @SWG\Property(description="Textual description of any special cases for obtaining the service, accepts html tags")
   */
  private $specialCases;

  /**
   * @var string
   * @Serializer\Type("string")
   * @SWG\Property(description="Other info, accepts html tags")
   */
  private $moreInfo;

  /**
   * @var string
   * @Serializer\Type("string")
   * @SWG\Property(description="Information shown to the citizen during the compilation of the service, accepts html tags")
   */
  private $compilationInfo;

  /**
   * @var string
   * @Serializer\Type("string")
   * @SWG\Property(description="Indications shown to the citizen at the end of the compilation of the service, accepts html tags")
   */
  private $finalIndications;

  /**
   * @var string[]
   * @Serializer\Type("array<string>")
   * @SWG\Property(description="Geographical area covered by service", type="array", @SWG\Items(type="string"))
   */
  private $coverage;

  /**
   * @var string
   * @Serializer\Type("string")
   * @SWG\Property(description="Response type from service, possible values: <br/> standard - Operator can accept or reject the application <br/> attachment - Operator can accept or reject the application and in case of acceptance, attach a response file <br/> signed_attachment - Operator can accept or reject the application and in case of acceptance, attach a signed response file")
   */
  private $response_type;

  /**
   * @var FlowStep[]
   * @Assert\NotBlank(message="You have to specify at least one step: flow_steps")
   * @Assert\NotNull(message="You have to specify at least one step: flow_steps")
   * @SWG\Property(property="flow_steps", type="array", @SWG\Items(ref=@Model(type=FlowStep::class)))
   * @Serializer\Type("array")
   */
  private $flowSteps;

  /**
   * @var array
   * @SWG\Property(property="protocollo_parameters", description="Service's parameters for tenant's register"))
   * @Serializer\Type("array<string, string>")
   */
  private $protocolloParameters;

  /**
   * @var bool
   * @Serializer\Type("boolean")
   * @SWG\Property(description="Set true if a payment is required")
   */
  private $paymentRequired;

  /**
   * @var array
   * @SWG\Property(property="payment_parameters", description="List of payment gateways available for the service and related parameters", type="object", ref=@Model(type=PaymentParameters::class))
   * @Serializer\Type("array")
   */
  private $paymentParameters;


  /**
   * @var bool
   * @Serializer\Type("boolean")
   * @SWG\Property(description="If selected the service will be shown at the top of the page")
   */
  private $sticky;

  /**
   * @Assert\NotBlank(message="This field is mandatory: name")
   * @Assert\NotNull(message="This field is mandatory: name")
   * @Serializer\Type("integer")
   * @SWG\Property(description="Accepts values: 0 - Hidden, 1 - Pubblished, 2 - Suspended")
   */
  private $status;

  /**
   * @var bool
   * @SWG\Property(description="Enable or disable the suggestion to log in to auto-complete some fields")
   */
  private $loginSuggested;

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
   * @return array
   */
  public function getProtocolloParameters()
  {
    return $this->protocolloParameters;
  }

  /**
   * @param array $protocolloParameters
   */
  public function setProtocolloParameters( $protocolloParameters)
  {
    if (!is_array($protocolloParameters)) {
      $parameters = json_decode($protocolloParameters, true);
    }
    $this->protocolloParameters = $parameters;
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
   */
  public function setPaymentRequired(bool $paymentRequired)
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
   * @param Servizio $servizio
   * @return Service
   */
  public static function fromEntity(Servizio $servizio)
  {
    $dto = new self();
    $dto->id = $servizio->getId();
    $dto->name = $servizio->getName();
    $dto->slug = $servizio->getSlug();
    $dto->tenant = $servizio->getEnteId();

    $dto->topics = $servizio->getTopics() ? $servizio->getTopics()->getSlug() : null;
    $dto->description = $servizio->getDescription() ?? '';
    $dto->howto = $servizio->getHowto();
    $dto->who = $servizio->getWho() ?? '';
    $dto->specialCases = $servizio->getSpecialCases() ?? '';
    $dto->moreInfo = $servizio->getMoreInfo() ?? '';
    $dto->compilationInfo = $servizio->getCompilationInfo() ?? '';
    $dto->finalIndications = $servizio->getFinalIndications() ?? '';
    $dto->coverage = $servizio->getCoverage();
    $dto->flowSteps = $servizio->getFlowSteps();
    $dto->protocolloParameters = null;
    $dto->paymentRequired = $servizio->isPaymentRequired();
    $dto->paymentParameters = [];
    $dto->sticky = $servizio->isSticky();
    $dto->status = $servizio->getStatus();
    $dto->loginSuggested = $servizio->isLoginSuggested() || false;

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
    $entity->setHowto($this->howto);
    $entity->setWho($this->who ?? '');
    $entity->setSpecialCases($this->specialCases ?? '');
    $entity->setMoreInfo($this->moreInfo ?? '');
    $entity->setCompilationInfo($this->compilationInfo ?? '');
    $entity->setFinalIndications($this->finalIndications ?? '');
    $entity->setCoverage(implode(',', (array)$this->coverage)); //@TODO

    if (count($this->flowSteps) > 0) {
      $temp = [];
      foreach ($this->flowSteps as $f) {
        $f->setParameters(\json_decode($f->getParameters(), true));
        $temp[]= $f;
      }
      $this->flowSteps = $temp;
    }
    $entity->setFlowSteps($this->flowSteps);
    $entity->setProtocolloParameters($this->protocolloParameters);
    $entity->setPaymentRequired($this->paymentRequired);
    $entity->setPaymentParameters($this->paymentParameters);
    $entity->setSticky($this->sticky);
    $entity->setStatus($this->status);
    $entity->setLoginSuggested($this->loginSuggested);

    return $entity;
  }


}
