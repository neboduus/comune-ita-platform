<?php


namespace App\Model;

use App\Entity\Allegato;
use App\Entity\Meeting;
use App\Entity\ModuloCompilato;
use DateTime;
use JMS\Serializer\Annotation as Serializer;
use JMS\Serializer\Annotation\Groups;
use OpenApi\Annotations as OA;

class Application
{

  /**
   * @Serializer\Type("string")
   * @OA\Property(description="Application's uuid")
   * @Groups({"read"})
   */
  protected $id;

  /**
   * @Serializer\Type("string")
   * @OA\Property(description="Applications's user (uuid)")
   * @Groups({"read", "write"})
   */
  private $user;

  /**
   * @Serializer\Type("string")
   * @OA\Property(description="Applications's user name")
   * @Groups({"read"})
   */
  private $userName;


  /**
   * @Serializer\Type("string")
   * @OA\Property(description="Applications's service slug")
   * @Groups({"read"})
   * @OA\Property(description="Applications's service (slug)")
   * @Groups({"read", "write"})
   */
  private $service;

  /**
   * @Serializer\Type("string")
   * @OA\Property(description="Applications's service ID")
   * @Groups({"read"})
   */
  private $serviceId;

  /**
   * @Serializer\Type("string")
   * @OA\Property(description="Applications's service name")
   * @Groups({"read"})
   */
  private $serviceName;

  /**
   * @Serializer\Type("string")
   * @OA\Property(description="Applications's service group name")
   * @Groups({"read"})
   */
  private $serviceGroupName;

  /**
   * @Serializer\Type("string")
   * @OA\Property(description="Applications's tenant (uuid)")
   * @Groups({"read"})
   */
  private $tenant;

  /**
   * @Serializer\Type("string")
   * @OA\Property(description="Applications's subject")
   * @Groups({"read"})
   */
  private $subject;

  /**
   * @var array
   * @OA\Property(property="data", description="Applcation's data")
   * @Groups({"read", "write"})
   * @Serializer\Type("array")
   */
  private $data;

  /**
   * @var ModuloCompilato[]
   * @OA\Property(property="compiled_modules", description="Compiled module file")
   * @Serializer\Type("array")
   * @Groups({"read"})
   */
  private $compiledModules;

  /**
   * @var Allegato[]
   * @OA\Property(property="attachments", description="Attachments list", type="array", @OA\Items(type="object"))
   * @Serializer\Type("array")
   * @Groups({"read"})
   */
  private $attachments;


  /**
   * @Serializer\Type("int")
   * @OA\Property(description="Creation time", type="integer")
   * @Groups({"read"})
   */
  private $creationTime;

  /**
   * @Serializer\Type("DateTime")
   * @OA\Property(description="Creation date time", type="string", format="date-time")
   * @Groups({"read"})
   */
  private $createdAt;

  /**
   * @Serializer\Type("int")
   * @OA\Property(description="Submission time", type="integer")
   * @Groups({"read"})
   */
  private $submissionTime;

  /**
   * @Serializer\Type("DateTime")
   * @OA\Property(description="Submission date time", type="string", format="date-time")
   * @Groups({"read"})
   */
  private $submittedAt;

  /**
   * @Serializer\Type("int")
   * @OA\Property(description="Latest status change timestamp", type="integer")
   * @Groups({"read"})
   */
  private $latestStatusChangeTime;

  /**
   * @Serializer\Type("DateTime")
   * @OA\Property(description="Latest status change time", type="string", format="date-time")
   * @Groups({"read"})
   */
  private $latestStatusChangeAt;

  /**
   * @Serializer\Type("string")
   * @OA\Property(description="Applications's protocol folder number")
   * @Groups({"read", "write"})
   */
  private $protocolFolderNumber;

  /**
   * @Serializer\Type("string")
   * @OA\Property(description="Applications's protocol folder code")
   * @Groups({"read", "write"})
   */
  private $protocolFolderCode;

  /**
   * @Serializer\Type("string")
   * @OA\Property(description="Applications's protocol number")
   * @Groups({"read", "write"})
   */
  private $protocolNumber;

  /**
   * @Serializer\Type("string")
   * @OA\Property(description="Applications's protocol document number")
   * @Groups({"read", "write"})
   */
  private $protocolDocumentId;

  /**
   * @var array
   * @OA\Property(property="protocol_numbers", type="array", @OA\Items(type="object"), description="Protocol numbers related to application")
   * @Serializer\Type("array<array>")
   * @Groups({"read"})
   */
  private $protocolNumbers;

  /**
   * @Serializer\Type("int")
   * @OA\Property(description="Protocol time", type="integer")
   * @Groups({"read"})
   */
  private $protocolTime;

  /**
   * @Serializer\Type("DateTime")
   * @OA\Property(description="Protocol date time", type="string", format="date-time")
   * @Groups({"read", "write"})
   */
  private $protocolledAt;

  /**
   * @var bool
   * @Serializer\Type("boolean")
   * @OA\Property(description="If selected the service will be shown at the top of the page")
   * @Groups({"read", "write"})
   */
  private $outcome;

  /**
   * @Serializer\Type("string")
   * @OA\Property(description="Outocome motivation")
   * @Groups({"read", "write"})
   */
  private $outcomeMotivation;

  /**
   * @var Allegato
   * @OA\Property(property="outcome_file", type="string", description="Outocome file")
   * @Serializer\Type("array")
   * @Groups({"read"})
   */
  private $outcomeFile;

  /**
   * @var Allegato[]
   * @OA\Property(property="outcome_attachments", description="Outcome attachments list", type="array", @OA\Items(type="object"))
   * @Serializer\Type("array")
   * @Groups({"read"})
   */
  private $outcomeAttachments;

  /**
   * @Serializer\Type("string")
   * @OA\Property(description="Applications's outcome protocol number")
   * @Groups({"read", "write"})
   */
  private $outcomeProtocolNumber;

  /**
   * @Serializer\Type("string")
   * @OA\Property(description="Applications's outcome protocol document number")
   * @Groups({"read", "write"})
   */
  private $outcomeProtocolDocumentId;

  /**
   * @var array
   * @OA\Property(property="outcome_protocol_numbers", type="array", @OA\Items(type="object"), description="Protocol numbers related to application's outcome")
   * @Serializer\Type("array<array>")
   * @Groups({"read"})
   */
  private $outcomeProtocolNumbers;

  /**
   * @Serializer\Type("int")
   * @OA\Property(description="Outcome protocol time", type="integer")
   * @Groups({"read"})
   */
  private $outcomeProtocolTime;

  /**
   * @Serializer\Type("DateTime")
   * @OA\Property(description="Outcome protocol date time", type="string", format="date-time")
   * @Groups({"read", "write"})
   */
  private $outcomeProtocolledAt;


  /**
   * @Serializer\Type("string")
   * @OA\Property(description="Pyment gateway used")
   * @Groups({"read", "write"})
   */
  private $paymentType;

  /**
   * @var array
   * @OA\Property(property="payment_data", description="Payment data")
   * @Serializer\Type("array")
   * @Groups({"read", "write"})
   */
  private $paymentData;

  /**
   * @Serializer\Type("string")
   * @OA\Property(description="Applications status")
   * @Groups({"read", "write"})
   */
  private $status;

  /**
   * @Serializer\Type("string")
   * @OA\Property(description="Applications status name")
   * @Groups({"read"})
   */
  private $statusName;

  /**
   * @var array
   * @OA\Property(property="authentication", type="object", description="User authentication data")
   * @Groups({"read"})
   */
  private $authentication;

  /**
   * @Serializer\Type("array")
   * @OA\Property(description="Applications links")
   * @Groups({"read"})
   */
  private $links;

  /**
   * @var Meeting[]
   * @OA\Property(property="meetings", description="Application linked meetings", type="array", @OA\Items(type="string"))
   * @Serializer\Type("array")
   * @Groups({"read"})
   */
  private $meetings;

  /**
   * @var Allegato[]
   * @OA\Property(property="integrations", description="Integrations list", type="array", @OA\Items(type="object"))
   * @Serializer\Type("array")
   * @Groups({"read"})
   */
  private $integrations;

  /**
   * @var array
   * @OA\Property(property="backoffice_data", description="Applcation's backoffice data")
   * @Groups({"read"})
   * @Serializer\Type("array")
   */
  private $backofficeData;

  /**
   * @Serializer\Type("DateTime")
   * @OA\Property(description="Flow change date time", type="string", format="date-time")
   * @Groups({"read"})
   */
  private $flowChangedAt;

  /**
   * @Serializer\Type("string")
   * @OA\Property(description="Applications notes user complitaion")
   * @Groups({"read", "write"})
   */
  private $userCompilationNotes;


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
   * @return mixed
   */
  public function getUser()
  {
    return $this->user;
  }

  /**
   * @param mixed $user
   */
  public function setUser($user)
  {
    $this->user = $user;
  }

  /**
   * @return mixed
   */
  public function getUserName()
  {
    return $this->userName;
  }

  /**
   * @param mixed $userName
   */
  public function setUserName($userName): void
  {
    $this->userName = $userName;
  }

  /**
   * @return mixed
   */
  public function getService()
  {
    return $this->service;
  }

  /**
   * @param mixed $service
   */
  public function setService($service)
  {
    $this->service = $service;
  }

  /**
   * @return mixed
   */
  public function getServiceId()
  {
    return $this->serviceId;
  }

  /**
   * @param mixed $serviceId
   */
  public function setServiceId($serviceId): void
  {
    $this->serviceId = $serviceId;
  }

  /**
   * @return mixed
   */
  public function getServiceName()
  {
    return $this->serviceName;
  }

  /**
   * @param mixed $serviceName
   */
  public function setServiceName($serviceName): void
  {
    $this->serviceName = $serviceName;
  }

  /**
   * @return mixed
   */
  public function getServiceGroupName()
  {
    return $this->serviceGroupName;
  }

  /**
   * @param mixed $serviceGroupName
   */
  public function setServiceGroupName($serviceGroupName): void
  {
    $this->serviceGroupName = $serviceGroupName;
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
  public function getSubject()
  {
    return $this->subject;
  }

  /**
   * @param mixed $subject
   */
  public function setSubject($subject)
  {
    $this->subject = $subject;
  }

  /**
   * @return array
   */
  public function getData(): ?array
  {
    return $this->data;
  }

  /**
   * @param array $data
   */
  public function setData($data)
  {
    if (is_array($data)) {
      $this->data = $data;
    } elseif (is_string($data)) {
      $this->data = json_decode($data, true);
    }
  }

  /**
   * @return ModuloCompilato[]
   */
  public function getCompiledModules(): array
  {
    return $this->compiledModules;
  }

  /**
   * @param ModuloCompilato[] $compiledModules
   */
  public function setCompiledModules(array $compiledModules)
  {
    $this->compiledModules = $compiledModules;
  }

  /**
   * @return Allegato[]
   */
  public function getAttachments(): array
  {
    return $this->attachments;
  }

  /**
   * @param Allegato[] $attachments
   */
  public function setAttachments(array $attachments)
  {
    $this->attachments = $attachments;
  }

  /**
   * @return int
   */
  public function getCreationTime()
  {
    return $this->creationTime;
  }

  /**
   * @param int $creationTime
   */
  public function setCreationTime($creationTime)
  {
    $this->creationTime = $creationTime;
  }

  /**
   * @return DateTime
   */
  public function getCreatedAt()
  {
    return $this->createdAt;
  }

  /**
   * @param DateTime $createdAt
   */
  public function setCreatedAt(DateTime $createdAt)
  {
    $this->createdAt = $createdAt;
  }

  /**
   * @return int
   */
  public function getSubmissionTime()
  {
    return $this->submissionTime;
  }

  /**
   * @param int $submissionTime
   */
  public function setSubmissionTime($submissionTime)
  {
    $this->submissionTime = $submissionTime;
  }

  /**
   * @return DateTime
   */
  public function getSubmittedAt(): ?DateTime
  {
    return $this->submittedAt;
  }

  /**
   * @param DateTime $submittedAt
   */
  public function setSubmittedAt(?DateTime $submittedAt)
  {
    $this->submittedAt = $submittedAt;
  }

  /**
   * @return mixed
   */
  public function getProtocolFolderNumber()
  {
    return $this->protocolFolderNumber;
  }

  /**
   * @param mixed $protocolFolderNumber
   */
  public function setProtocolFolderNumber($protocolFolderNumber)
  {
    $this->protocolFolderNumber = $protocolFolderNumber;
  }

  /**
   * @return mixed
   */
  public function getProtocolFolderCode()
  {
    return $this->protocolFolderCode;
  }

  /**
   * @param mixed $protocolFolderCode
   */
  public function setProtocolFolderCode($protocolFolderCode)
  {
    $this->protocolFolderCode = $protocolFolderCode;
  }

  /**
   * @return mixed
   */
  public function getProtocolNumber()
  {
    return $this->protocolNumber;
  }

  /**
   * @param mixed $protocolNumber
   */
  public function setProtocolNumber($protocolNumber)
  {
    $this->protocolNumber = $protocolNumber;
  }

  /**
   * @return mixed
   */
  public function getProtocolDocumentId()
  {
    return $this->protocolDocumentId;
  }

  /**
   * @param mixed $protocolDocumentId
   */
  public function setProtocolDocumentId($protocolDocumentId)
  {
    $this->protocolDocumentId = $protocolDocumentId;
  }

  /**
   * @return array
   */
  public function getProtocolNumbers(): array
  {
    return $this->protocolNumbers;
  }

  /**
   * @param array $protocolNumbers
   */
  public function setProtocolNumbers(array $protocolNumbers)
  {
    $this->protocolNumbers = $protocolNumbers;
  }

  /**
   * @return int
   */
  public function getProtocolTime()
  {
    return $this->protocolTime;
  }

  /**
   * @param int $protocolTime
   */
  public function setProtocolTime($protocolTime)
  {
    $this->protocolTime = $protocolTime;
  }

  /**
   * @return DateTime
   */
  public function getProtocolledAt(): ?DateTime
  {
    return $this->protocolledAt;
  }

  /**
   * @param DateTime $protocolledAt
   */
  public function setProtocolledAt(?DateTime $protocolledAt)
  {
    $this->protocolledAt = $protocolledAt;
  }

  /**
   * @return bool
   */
  public function isOutcome(): ?bool
  {
    return $this->outcome;
  }

  /**
   * @param bool $outcome
   */
  public function setOutcome(?bool $outcome)
  {
    $this->outcome = $outcome;
  }

  /**
   * @return mixed
   */
  public function getOutcomeMotivation()
  {
    return $this->outcomeMotivation;
  }

  /**
   * @param mixed $outcomeMotivation
   */
  public function setOutcomeMotivation($outcomeMotivation)
  {
    $this->outcomeMotivation = $outcomeMotivation;
  }

  /**
   * @return Allegato
   */
  public function getOutcomeFile()
  {
    return $this->outcomeFile;
  }

  /**
   * @param $outcomeFile
   */
  public function setOutcomeFile($outcomeFile)
  {
    $this->outcomeFile = $outcomeFile;
  }

  /**
   * @return Allegato[]
   */
  public function getOutcomeAttachments(): array
  {
    return $this->outcomeAttachments;
  }

  /**
   * @param Allegato[] $outcomeAttachments
   */
  public function setOutcomeAttachments(array $outcomeAttachments)
  {
    $this->outcomeAttachments = $outcomeAttachments;
  }

  /**
   * @return mixed
   */
  public function getOutcomeProtocolNumber()
  {
    return $this->outcomeProtocolNumber;
  }

  /**
   * @param mixed $outcomeProtocolNumber
   */
  public function setOutcomeProtocolNumber($outcomeProtocolNumber)
  {
    $this->outcomeProtocolNumber = $outcomeProtocolNumber;
  }

  /**
   * @return mixed
   */
  public function getOutcomeProtocolDocumentId()
  {
    return $this->outcomeProtocolDocumentId;
  }

  /**
   * @param mixed $outcomeProtocolDocumentId
   */
  public function setOutcomeProtocolDocumentId($outcomeProtocolDocumentId)
  {
    $this->outcomeProtocolDocumentId = $outcomeProtocolDocumentId;
  }

  /**
   * @return array
   */
  public function getOutcomeProtocolNumbers(): ?array
  {
    return $this->outcomeProtocolNumbers;
  }

  /**
   * @param array $outcomeProtocolNumbers
   */
  public function setOutcomeProtocolNumbers(?array $outcomeProtocolNumbers)
  {
    $this->outcomeProtocolNumbers = $outcomeProtocolNumbers;
  }

  /**
   * @return int
   */
  public function getOutcomeProtocolTime()
  {
    return $this->outcomeProtocolTime;
  }

  /**
   * @param int $outcomeProtocolTime
   */
  public function setOutcomeProtocolTime($outcomeProtocolTime)
  {
    $this->outcomeProtocolTime = $outcomeProtocolTime;
  }

  /**
   * @return DateTime
   */
  public function getOutcomeProtocolledAt(): ?Datetime
  {
    return $this->outcomeProtocolledAt;
  }

  /**
   * @param DateTime $outcomeProtocolledAt
   */
  public function setOutcomeProtocolledAt(?DateTime $outcomeProtocolledAt)
  {
    $this->outcomeProtocolledAt = $outcomeProtocolledAt;
  }

  /**
   * @return mixed
   */
  public function getPaymentType()
  {
    return $this->paymentType;
  }

  /**
   * @param mixed $paymentType
   */
  public function setPaymentType($paymentType)
  {
    $this->paymentType = $paymentType;
  }

  /**
   * @return array
   */
  public function getPaymentData(): ?array
  {
    return $this->paymentData;
  }

  /**
   * @param array $paymentData
   */
  public function setPaymentData(array $paymentData)
  {
    $this->paymentData = $paymentData;
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
  public function getStatusName()
  {
    return $this->statusName;
  }

  /**
   * @param mixed $statusName
   */
  public function setStatusName($statusName)
  {
    $this->statusName = $statusName;
  }

  /**
   * @return array
   */
  public function getAuthentication()
  {
    return $this->authentication;
  }

  /**
   * @param array $authentication
   */
  public function setAuthentication($authentication)
  {
    $this->authentication = $authentication;
  }
  /**
   * @return Meeting[]
   */
  public function getMeetings(): array
  {
    return $this->meetings;
  }

  /**
   * @param Meeting[] $meetings
   */
  public function setMeetings(array $meetings)
  {
    $this->meetings = $meetings;
  }

  /**
   * @return Allegato[]
   */
  public function getIntegrations(): array
  {
    return $this->integrations;
  }

  /**
   * @param Allegato[] $integrations
   */
  public function setIntegrations(array $integrations): void
  {
    $this->integrations = $integrations;
  }

  /**
   * @return mixed
   */
  public function getLinks()
  {
    return $this->links;
  }

  /**
   * @param mixed $links
   */
  public function setLinks($links): void
  {
    $this->links = $links;
  }

  /**
   * @return array
   */
  public function getBackofficeData(): ?array
  {
    return $this->backofficeData;
  }

  /**
   * @param array $data
   */
  public function setBackofficeData($data)
  {
    if (is_array($data)) {
      $this->backofficeData = $data;
    } elseif (is_string($data)) {
      $this->backofficeData = json_decode($data, true);
    }
  }

  /**
   * @return mixed
   */
  public function getLatestStatusChangeTime()
  {
    return $this->latestStatusChangeTime;
  }

  /**
   * @param mixed $latestStatusChangeTime
   */
  public function setLatestStatusChangeTime($latestStatusChangeTime): void
  {
    $this->latestStatusChangeTime = $latestStatusChangeTime;
  }

  /**
   * @return mixed
   */
  public function getLatestStatusChangeAt()
  {
    return $this->latestStatusChangeAt;
  }

  /**
   * @param mixed $latestStatusChangeAt
   */
  public function setLatestStatusChangeAt($latestStatusChangeAt): void
  {
    $this->latestStatusChangeAt = $latestStatusChangeAt;
  }

  /**
   * @return mixed
   */
  public function getFlowChangedAt()
  {
    return $this->flowChangedAt;
  }

  /**
   * @param mixed $flowChangedAt
   */
  public function setFlowChangedAt($flowChangedAt): void
  {
    $this->flowChangedAt = $flowChangedAt;
  }

  /**
   * @return string
   */
  public function getUserCompilationNotes()
  {
    return $this->userCompilationNotes;
  }

  /**
   * @param string $userCompilationNotes
   *
   * @return $this
   */
  public function setUserCompilationNotes(?string $userCompilationNotes)
  {
    $this->userCompilationNotes = $userCompilationNotes;

    return $this;
  }

}
