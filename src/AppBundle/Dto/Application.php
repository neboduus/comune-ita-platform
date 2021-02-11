<?php


namespace AppBundle\Dto;

use AppBundle\Entity\Allegato;
use AppBundle\Entity\ModuloCompilato;
use AppBundle\Entity\Pratica;
use AppBundle\Entity\UserSession;
use AppBundle\Payment\PaymentDataInterface;
use AppBundle\Services\PraticaStatusService;
use DateTime;
use JMS\Serializer\Annotation as Serializer;
use JMS\Serializer\Annotation\Groups;
use Swagger\Annotations as SWG;

class Application
{

  /**
   * @Serializer\Type("string")
   * @SWG\Property(description="Application's uuid")
   * @Groups({"read"})
   */
  protected $id;

  /**
   * @Serializer\Type("string")
   * @SWG\Property(description="Applications's user (uuid)")
   * @Groups({"read"})
   */
  private $user;

  /**
   * @Serializer\Type("string")
   * @SWG\Property(description="Applications's user name")
   * @Groups({"read"})
   */
  private $userName;


  /**
   * @Serializer\Type("string")
   * @SWG\Property(description="Applications's service (slug)")
   * @Groups({"read"})
   */
  private $service;


  /**
   * @Serializer\Type("string")
   * @SWG\Property(description="Applications's tenant (uuid)")
   * @Groups({"read"})
   */
  private $tenant;

  /**
   * @Serializer\Type("string")
   * @SWG\Property(description="Applications's subject")
   * @Groups({"read"})
   */
  private $subject;

  /**
   * @var array
   * @SWG\Property(property="data", description="Applcation's data")
   * @Groups({"read"})
   * @Serializer\Type("array")
   */
  private $data;

  /**
   * @var ModuloCompilato[]
   * @SWG\Property(property="compiled_modules", description="Compiled module file")
   * @Serializer\Type("array")
   * @Groups({"read"})
   */
  private $compiledModules;

  /**
   * @var Allegato[]
   * @SWG\Property(property="attachments", description="Attachments list", type="array", @SWG\Items(type="object"))
   * @Serializer\Type("array")
   * @Groups({"read"})
   */
  private $attachments;


  /**
   * @Serializer\Type("int")
   * @SWG\Property(description="Creation time", type="int")
   * @Groups({"read"})
   */
  private $creationTime;

  /**
   * @Serializer\Type("DateTime")
   * @SWG\Property(description="Creation date time", type="dateTime")
   * @Groups({"read"})
   */
  private $createdAt;

  /**
   * @Serializer\Type("int")
   * @SWG\Property(description="Submission time", type="int")
   * @Groups({"read"})
   */
  private $submissionTime;

  /**
   * @Serializer\Type("DateTime")
   * @SWG\Property(description="Submission date time", type="dateTime")
   * @Groups({"read"})
   */
  private $submittedAt;

  /**
   * @Serializer\Type("int")
   * @SWG\Property(description="Latest status change timestamp", type="int")
   * @Groups({"read"})
   */
  private $latestStatusChangeTime;

  /**
   * @Serializer\Type("DateTime")
   * @SWG\Property(description="Latest status change time", type="dateTime")
   * @Groups({"read"})
   */
  private $latestStatusChangeAt;

  /**
   * @Serializer\Type("string")
   * @SWG\Property(description="Applications's protocol folder number")
   * @Groups({"read", "write"})
   */
  private $protocolFolderNumber;

  /**
   * @Serializer\Type("string")
   * @SWG\Property(description="Applications's protocol number")
   * @Groups({"read", "write"})
   */
  private $protocolNumber;

  /**
   * @Serializer\Type("string")
   * @SWG\Property(description="Applications's protocol document number")
   * @Groups({"read", "write"})
   */
  private $protocolDocumentId;

  /**
   * @var array
   * @SWG\Property(property="protocol_numbers", type="array", @SWG\Items(type="object"), description="Protocol numbers related to application")
   * @Serializer\Type("array<array>")
   * @Groups({"read"})
   */
  private $protocolNumbers;

  /**
   * @Serializer\Type("int")
   * @SWG\Property(description="Protocol time", type="int")
   * @Groups({"read"})
   */
  private $protocolTime;

  /**
   * @Serializer\Type("DateTime")
   * @SWG\Property(description="Protocol date time", type="dateTime")
   * @Groups({"read", "write"})
   */
  private $protocolledAt;

  /**
   * @var bool
   * @Serializer\Type("boolean")
   * @SWG\Property(description="If selected the service will be shown at the top of the page")
   * @Groups({"read"})
   */
  private $outcome;

  /**
   * @Serializer\Type("string")
   * @SWG\Property(description="Outocome motivation")
   * @Groups({"read"})
   */
  private $outcomeMotivation;

  /**
   * @var Allegato
   * @SWG\Property(property="outcome_file", type="string", description="Outocome file")
   * @Serializer\Type("array")
   * @Groups({"read"})
   */
  private $outcomeFile;

  /**
   * @var Allegato[]
   * @SWG\Property(property="outcome_attachments", description="Outcome attachments list", type="array", @SWG\Items(type="object"))
   * @Serializer\Type("array")
   * @Groups({"read"})
   */
  private $outcomeAttachments;

  /**
   * @Serializer\Type("string")
   * @SWG\Property(description="Applications's outcome protocol number")
   * @Groups({"read", "write"})
   */
  private $outcomeProtocolNumber;

  /**
   * @Serializer\Type("string")
   * @SWG\Property(description="Applications's outcome protocol document number")
   * @Groups({"read", "write"})
   */
  private $outcomeProtocolDocumentId;

  /**
   * @var array
   * @SWG\Property(property="outcome_protocol_numbers", type="array", @SWG\Items(type="object"), description="Protocol numbers related to application's outcome")
   * @Serializer\Type("array<array>")
   * @Groups({"read"})
   */
  private $outcomeProtocolNumbers;

  /**
   * @Serializer\Type("int")
   * @SWG\Property(description="Outcome protocol time", type="int")
   * @Groups({"read"})
   */
  private $outcomeProtocolTime;

  /**
   * @Serializer\Type("DateTime")
   * @SWG\Property(description="Outcome protocol date time", type="dateTime")
   * @Groups({"read", "write"})
   */
  private $outcomeProtocolledAt;


  /**
   * @Serializer\Type("string")
   * @SWG\Property(description="Pyment gateway used")
   * @Groups({"read"})
   */
  private $paymentType;

  /**
   * @var array
   * @SWG\Property(property="payment_data", description="Payment data")
   * @Serializer\Type("array")
   * @Groups({"read"})
   */
  private $paymentData;

  /**
   * @Serializer\Type("string")
   * @SWG\Property(description="Applications status")
   * @Groups({"read"})
   */
  private $status;

  /**
   * @Serializer\Type("string")
   * @SWG\Property(description="Applications status name")
   * @Groups({"read"})
   */
  private $statusName;

  /**
   * @var array
   * @SWG\Property(property="authentication", type="object", description="User authentication data")
   * @Groups({"read"})
   */
  private $authentication;

  /**
   * @Serializer\Type("array")
   * @SWG\Property(description="Applications links")
   * @Groups({"read"})
   */
  private $links;

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
  public function getData(): array
  {
    return $this->data;
  }

  /**
   * @param array $data
   */
  public function setData(array $data)
  {
    $this->data = $data;
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
  public function getSubmittedAt(): DateTime
  {
    return $this->submittedAt;
  }

  /**
   * @param DateTime $submittedAt
   */
  public function setSubmittedAt(DateTime $submittedAt)
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
  public function getProtocolledAt()
  {
    return $this->protocolledAt;
  }

  /**
   * @param DateTime $protocolledAt
   */
  public function setProtocolledAt(DateTime $protocolledAt)
  {
    $this->protocolledAt = $protocolledAt;
  }

  /**
   * @return bool
   */
  public function isOutcome(): bool
  {
    return $this->outcome;
  }

  /**
   * @param bool $outcome
   */
  public function setOutcome(bool $outcome)
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
  public function getOutcomeFile(): Allegato
  {
    return $this->outcomeFile;
  }

  /**
   * @param Allegato $outcomeFile
   */
  public function setOutcomeFile(Allegato $outcomeFile)
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
  public function setOutcomeProtocolNumbers(array $outcomeProtocolNumbers)
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
  public function getOutcomeProtocolledAt()
  {
    return $this->outcomeProtocolledAt;
  }

  /**
   * @param DateTime $outcomeProtocolledAt
   */
  public function setOutcomeProtocolledAt(DateTime $outcomeProtocolledAt)
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
  public function getPaymentData(): array
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
   * @param Pratica $pratica
   * @param string $attachmentEndpointUrl
   * @param bool $loadFileCollection default is true, if false: avoids additional queries for file loading
   * @return Application
   */
  public static function fromEntity(Pratica $pratica, $attachmentEndpointUrl = '', $loadFileCollection = true, $version = 1)
  {

    $dto = new self();
    $dto->id = $pratica->getId();
    $dto->user = $pratica->getUser()->getId();
    $dto->userName = $pratica->getUser()->getFullName();
    $dto->tenant = $pratica->getEnte()->getId();
    $dto->service = $pratica->getServizio()->getSlug();
    $dto->subject = $pratica->getOggetto();

    if ($pratica->getServizio()->getPraticaFCQN() == '\AppBundle\Entity\FormIO') {
      if ($version >= 2) {
        $dto->data = self::decorateDematerializedFormsV2($pratica->getDematerializedForms(), $attachmentEndpointUrl);
      } else {
        $dto->data = self::decorateDematerializedForms($pratica->getDematerializedForms(), $attachmentEndpointUrl);
      }
    } else {
      $dto->data = [];
    }

    $dto->compiledModules = $loadFileCollection ? self::prepareFileCollection($pratica->getModuliCompilati(), $attachmentEndpointUrl) : [];

    $dto->outcomeFile = ($loadFileCollection && $pratica->getRispostaOperatore() instanceof Allegato) ? self::prepareFile($pratica->getRispostaOperatore(), $attachmentEndpointUrl) : null;
    $dto->outcome = $pratica->getEsito();
    $dto->outcomeMotivation = $pratica->getMotivazioneEsito();

    $dto->attachments = self::prepareFileCollection($pratica->getAllegati(), $attachmentEndpointUrl);
    $dto->outcomeAttachments = self::prepareFileCollection($pratica->getAllegatiOperatore(), $attachmentEndpointUrl);

    $dto->creationTime = $pratica->getCreationTime();
    try {
      $date = new \DateTime();
      $dto->createdAt = $date->setTimestamp($pratica->getCreationTime());
    } catch (\Exception $e) {
      $dto->createdAt = $pratica->getCreationTime();
    }

    $dto->submissionTime = $pratica->getSubmissionTime();
    if ($pratica->getSubmissionTime()) {
      try {
        $date = new \DateTime();
        $dto->submittedAt = $date->setTimestamp($pratica->getSubmissionTime());
      } catch (\Exception $e) {
        $dto->submittedAt = $pratica->getSubmissionTime();
      }
    }

    $dto->latestStatusChangeTime = $pratica->getLatestStatusChangeTimestamp();
    if ($pratica->getLatestStatusChangeTimestamp()) {
      try {
        $date = new \DateTime();
        $dto->latestStatusChangeAt = $date->setTimestamp($pratica->getLatestStatusChangeTimestamp());
      } catch (\Exception $e) {
        $dto->latestStatusChangeAt = $pratica->getLatestStatusChangeTimestamp();
      }
    }

    $dto->protocolFolderNumber = $pratica->getNumeroFascicolo();
    $dto->protocolNumber = $pratica->getNumeroProtocollo();
    $dto->protocolDocumentId = $pratica->getIdDocumentoProtocollo();
    $dto->protocolNumbers = $pratica->getNumeriProtocollo()->toArray();

    if ($pratica->getProtocolTime()) {
      $dto->protocolTime = $pratica->getProtocolTime();
      try {
        $date = new \DateTime();
        $dto->protocolledAt = $date->setTimestamp($pratica->getProtocolTime());
      } catch (\Exception $e) {
        $dto->protocolledAt = $pratica->getProtocolTime();
      }
    }

    $dto->outcome = $pratica->getEsito();

    if ($pratica->getRispostaOperatore()) {
      $dto->outcomeProtocolNumber = $pratica->getRispostaOperatore()->getNumeroProtocollo();
      $dto->outcomeProtocolDocumentId = $pratica->getRispostaOperatore()->getIdDocumentoProtocollo();
      $dto->outcomeProtocolNumbers = $pratica->getRispostaOperatore()->getNumeriProtocollo()->toArray();
      if ($pratica->getRispostaOperatore()->getProtocolTime()) {
        $dto->outcomeProtocolTime = $pratica->getRispostaOperatore()->getProtocolTime();
        try {
          $date = new \DateTime();
          $dto->outcomeProtocolledAt = $date->setTimestamp($pratica->getRispostaOperatore()->getProtocolTime());
        } catch (\Exception $e) {
          $dto->outcomeProtocolledAt = $pratica->getRispostaOperatore()->getProtocolTime();
        }
      }
    }

    //$dto->outcomeMotivation = $pratica->getMotivazioneEsito();
    //$dto->outcomeFile = $pratica->getRispostaOperatore();

    $dto->paymentType = $pratica->getPaymentType();
    $dto->paymentData = self::preparePaymentData($pratica);
    $dto->status = $pratica->getStatus();
    $dto->statusName = strtolower($pratica->getStatusName());

    $dto->authentication = ($pratica->getAuthenticationData()->getAuthenticationMethod() ?
      $pratica->getAuthenticationData() :
      UserAuthenticationData::fromArray(['authenticationMethod' => $pratica->getUser()->getIdp()]));

    // Fix for empty values
    if ($pratica->getSessionData() instanceof UserSession) {
      $sessionData = $pratica->getSessionData()->getSessionData();
      if (empty($dto->authentication->offsetGet('sessionIndex')) && isset($sessionData['shibSessionIndex'])) {
        $dto->authentication->offsetSet('sessionIndex', $sessionData['shibSessionIndex']);
      }
      if (empty($dto->authentication->offsetGet('instant')) && isset($sessionData['shibAuthenticationIstant'])) {
        $dto->authentication->offsetSet('instant', $sessionData['shibAuthenticationIstant']);
      }
    }


    $dto->setLinks(self::getAvailableTransitions($pratica, $attachmentEndpointUrl));
    return $dto;
  }

  public static function decorateDematerializedForms($data, $attachmentEndpointUrl = '')
  {
    if (!isset($data['flattened'])) {
      return $data;
    }
    $decoratedData = $data['flattened'];
    foreach ($decoratedData as $k => $v) {

      if (self::isUploadField($data['schema'], $k)) {
        $decoratedData[$k] = self::prepareFormioFile($v, $attachmentEndpointUrl);
      }

      if (self::isDateField($k)) {
        $decoratedData[$k] = self::prepareDateField($v);
      }
    }
    return $decoratedData;
  }

  public static function decorateDematerializedFormsV2($data, $attachmentEndpointUrl = '')
  {

    if (!isset($data['flattened'])) {
      return $data;
    }

    $decoratedData = $data['flattened'];
    $keys = array_keys($decoratedData);

    $multiArray = array();

    foreach ($keys as $path) {
      $parts = explode('.', trim($path, '.'));
      $section = &$multiArray;
      $sectionName = '';

      $partsCount = count($parts);
      $counter = 0;

      foreach ($parts as $part) {
        $counter++;
        $sectionName = $part;

        // Salto data
        if ($part === 'data') {
          continue;
        }

        if (array_key_exists($sectionName, $section) === false) {
          $section[$sectionName] = array();
        }

        // Se è l'ultimo elemento assegno il valore
        if ($counter == $partsCount) {
          if (self::isUploadField($data['schema'], $path)) {
            $section[$sectionName] = self::prepareFormioFile($decoratedData[$path], $attachmentEndpointUrl);
          } else if (self::isDateField($path)) {
            $section[$sectionName] = self::prepareDateField($decoratedData[$path]);
          } else {
            $section[$sectionName] = $decoratedData[$path];
          }
        }
        $section = &$section[$sectionName];

      }
    }

    return $multiArray;
  }

  public static function isUploadField($schema, $field)
  {
    return (isset($schema[$field . '.type']) && $schema[$field . '.type'] == 'file');
  }

  public static function prepareFileCollection($collection, $attachmentEndpointUrl = '')
  {
    $files = [];
    if ($collection == null) {
      return $files;
    }
    /** @var Allegato $c */
    foreach ($collection as $c) {
      $files[] = self::prepareFile($c, $attachmentEndpointUrl);
    }
    return $files;
  }

  public static function prepareFormioFile($files, $attachmentEndpointUrl = '')
  {
    $result = [];
    foreach ($files as $f) {
      $id = $f['data']['id'];
      $temp['id'] = $id;
      $temp['name'] = $f['name'];
      $temp['url'] = $attachmentEndpointUrl . '/attachments/' . $id;
      $temp['originalName'] = $f['originalName'];
      $temp['description'] = isset($f['fileType']) ? $f['fileType'] : Allegato::DEFAULT_DESCRIPTION;
      $result[] = $temp;
    }
    return $result;
  }

  public static function prepareFile(Allegato $file, $attachmentEndpointUrl = '')
  {
    $temp['id'] = $file->getId();
    $temp['name'] = $file->getName();
    $temp['url'] = $attachmentEndpointUrl . '/attachments/' . $file->getId();
    $temp['originalName'] = $file->getFilename();
    $temp['description'] = $file->getDescription() ?? Allegato::DEFAULT_DESCRIPTION;
    $temp['created_at'] = $file->getCreatedAt();

    return $temp;
  }

  public static function isDateField($keyField)
  {
    $parts = explode('.', $keyField);
    if (end($parts) === 'natoAIl') {
      return true;
    }
    return false;
  }

  public static function prepareDateField($value)
  {
    $date = str_replace('/', '-', $value);
    try {
      $parsedDate = new DateTime($date);
      return $parsedDate->format(DateTime::W3C);
    } catch (\Exception $e) {
      return '';
    }
  }

  /**
   * @param Pratica|null $entity
   * @return Pratica
   */
  public function toEntity(Pratica $entity = null)
  {
    if (!$entity) {
      $entity = new Pratica();
    }

    # Main document
    $entity->setNumeroProtocollo($this->getProtocolNumber());
    $entity->setNumeroFascicolo($this->getProtocolFolderNumber());
    $entity->setIdDocumentoProtocollo($this->getProtocolDocumentId());
    if ($this->getProtocolledAt()) {
      $entity->setProtocolTime($this->getProtocolledAt()->getTimestamp());
    }

    $applicationAttachments = array_merge($entity->getModuliCompilati()->getValues(), $entity->getAllegati()->getValues());

    foreach ($applicationAttachments as $attachment) {
      if ($this->getProtocolledAt()) {
        $attachment->setProtocolTime($this->getProtocolledAt()->getTimestamp());
      }
      $numeroDiProtocollo = [
        'id' => $attachment->getId(),
        'protocollo' => $this->getProtocolNumber(),
      ];

      if (!$this->inProtocolNumbers($numeroDiProtocollo, $entity->getNumeriProtocollo())) {
        $entity->addNumeroDiProtocollo($numeroDiProtocollo);
      }
    }


    # Outcome document
    $rispostaOperatore = $entity->getRispostaOperatore();
    if ($rispostaOperatore && $this->getOutcomeProtocolNumber()) {
      $rispostaOperatore->setNumeroProtocollo($this->getOutcomeProtocolNumber());
      $rispostaOperatore->setIdDocumentoProtocollo($this->getOutcomeProtocolDocumentId());
      if ($this->getOutcomeProtocolledAt()) {
        $rispostaOperatore->setProtocolTime($this->getOutcomeProtocolledAt()->getTimestamp());
      }

      $outcomeAttachments = array_merge([$entity->getRispostaOperatore()], $entity->getAllegatiOperatore()->getValues());

      foreach ($outcomeAttachments as $attachment) {
        if ($this->getOutcomeProtocolledAt()) {
          $attachment->setProtocolTime($this->getOutcomeProtocolledAt()->getTimestamp());
        }
        $numeroDiProtocollo = [
          'id' => $attachment->getId(),
          'protocollo' => $this->getOutcomeProtocolNumber(),
        ];

        if (!$this->inProtocolNumbers($numeroDiProtocollo, $rispostaOperatore->getNumeriProtocollo())) {
          $rispostaOperatore->addNumeroDiProtocollo($numeroDiProtocollo);
        }
      }
    }

    return $entity;
  }


  private function inProtocolNumbers($needle, $protocolNumbers)
  {
    $found = false;

    foreach ($protocolNumbers as $protocolNumber) {
      $protocolNumber = json_decode(json_encode($protocolNumber), true);
      if ($protocolNumber["id"] == $needle["id"] && $protocolNumber["protocollo"] == $needle["protocollo"]) {
        $found = true;
      }
    }
    return $found;
  }

  /**
   * @param Pratica $pratica
   * @return mixed
   */
  public static function preparePaymentData($pratica)
  {
    if (!empty($pratica->getPaymentData())) {
      $gateway = $pratica->getPaymentType();
      /** @var PaymentDataInterface $gatewayClassHandler */
      $gatewayClassHandler = $gateway->getFcqn();


      return $gatewayClassHandler::getSimplifiedData($pratica->getPaymentData());
    }
    return [];
  }


  /**
   * @param Pratica $pratica
   * @param string $baseUrl
   * @return array
   */
  public static function getAvailableTransitions(Pratica $pratica, $baseUrl = '')
  {
    $availableTransitions = [];
    if (isset(PraticaStatusService::TRANSITIONS_MAPPING[$pratica->getStatus()])) {
      $availableTransitions = PraticaStatusService::TRANSITIONS_MAPPING[$pratica->getStatus()];
      foreach ($availableTransitions as $k => $v) {
        // todo: fare refactoring completo della classe e generare con router
        $availableTransitions[$k]['url'] = $baseUrl . '/transiction/' . $v['action'];

        if ($v['action'] == 'register' && !$pratica->getServizio()->isProtocolRequired()) {
          unset($availableTransitions[$k]);
        }

        if ($v['action'] == 'withdraw' && !$pratica->getServizio()->isAllowReopening()) {
          unset($availableTransitions[$k]);
        }
      }
    }
    return $availableTransitions;
  }
}
