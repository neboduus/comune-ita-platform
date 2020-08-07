<?php


namespace AppBundle\Dto;

use AppBundle\Entity\Allegato;
use AppBundle\Entity\ModuloCompilato;
use AppBundle\Entity\PaymentGateway;
use AppBundle\Entity\Pratica;
use AppBundle\Mapper\Giscom\File;
use AppBundle\Mapper\Giscom\FileCollection;
use AppBundle\Payment\PaymentDataInterface;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\PersistentCollection;
use JMS\Serializer\Annotation as Serializer;
use phpDocumentor\Reflection\Types\Collection;
use phpDocumentor\Reflection\Types\Self_;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Nelmio\ApiDocBundle\Annotation\Model;
use Swagger\Annotations as SWG;
use Gedmo\Mapping\Annotation as Gedmo;
use JMS\Serializer\Annotation\AccessorOrder;

class Application
{

  /**
   * @Serializer\Type("string")
   * @SWG\Property(description="Application's uuid")
   */
  protected $id;

  /**
   * @Serializer\Type("string")
   * @SWG\Property(description="Applications's user (uuid)")
   */
  private $user;

  /**
   * @Serializer\Type("string")
   * @SWG\Property(description="Applications's user name")
   */
  private $userName;


  /**
   * @Serializer\Type("string")
   * @SWG\Property(description="Applications's service (slug)")
   */
  private $service;


  /**
   * @Serializer\Type("string")
   * @SWG\Property(description="Applications's tenant (uuid)")
   */
  private $tenant;

  /**
   * @Serializer\Type("string")
   * @SWG\Property(description="Applications's subject")
   */
  private $subject;

  /**
   * @var array
   * @SWG\Property(property="data", description="Applcation's data")
   * @Serializer\Type("array")
   */
  private $data;

  /**
   * @var ModuloCompilato[]
   * @SWG\Property(property="compiled_modules", description="Compiled module file")
   * @Serializer\Type("array")
   */
  private $compiledModules;

  /**
   * @var Allegato[]
   * @SWG\Property(property="attachments", description="Attachments list")
   * @Serializer\Type("string")
   */
  private $attachments;


  /**
   * @Serializer\Type("int")
   * @SWG\Property(description="Creation time", type="int")
   */
  private $creationTime;

  /**
   * @Serializer\Type("DateTime")
   * @SWG\Property(description="Creation date time", type="dateTime")
   */
  private $createdAt;

  /**
   * @Serializer\Type("int")
   * @SWG\Property(description="Submission time", type="int")
   */
  private $submissionTime;

  /**
   * @Serializer\Type("DateTime")
   * @SWG\Property(description="Submission date time", type="dateTime")
   */
  private $submittedAt;

  /**
   * @Serializer\Type("int")
   * @SWG\Property(description="Latest status change timestamp", type="int")
   */
  private $latestStatusChangeTime;

  /**
   * @Serializer\Type("DateTime")
   * @SWG\Property(description="Latest status change time", type="dateTime")
   */
  private $latestStatusChangeAt;

  /**
   * @Serializer\Type("string")
   * @SWG\Property(description="Applications's protocol folder number")
   */
  private $protocolFolderNumber;

  /**
   * @Serializer\Type("string")
   * @SWG\Property(description="Applications's protocol number")
   */
  private $protocolNumber;

  /**
   * @Serializer\Type("string")
   * @SWG\Property(description="Applications's protocol document number")
   */
  private $protocolDcoumentId;

  /**
   * @var String[]
   * @SWG\Property(property="protocol_numbers", type="string", description="Protocol numbers related to application")
   * @Serializer\Type("string")
   */
  private $protocolNumbers;

  /**
   * @var bool
   * @Serializer\Type("boolean")
   * @SWG\Property(description="If selected the service will be shown at the top of the page")
   */
  private $outcome;

  /**
   * @Serializer\Type("string")
   * @SWG\Property(description="Outocome motivation")
   */
  private $outcomeMotivation;

  /**
   * @var Allegato
   * @SWG\Property(property="outcome_file", type="string", description="Outocome file")
   * @Serializer\Type("array")
   */
  private $outcomeFile;

  /**
   * @Serializer\Type("string")
   * @SWG\Property(description="Pyment gateway used")
   */
  private $paymentType;

  /**
   * @var array
   * @SWG\Property(property="payment_data", description="Payment data")
   * @Serializer\Type("array")
   */
  private $paymentData;

  /**
   * @Serializer\Type("string")
   * @SWG\Property(description="Applications status")
   */
  private $status;

  /**
   * @Serializer\Type("string")
   * @SWG\Property(description="Applications status name")
   */
  private $statusName;


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
  public function getProtocolDcoumentId()
  {
    return $this->protocolDcoumentId;
  }

  /**
   * @param mixed $protocolDcoumentId
   */
  public function setProtocolDcoumentId($protocolDcoumentId)
  {
    $this->protocolDcoumentId = $protocolDcoumentId;
  }

  /**
   * @return String[]
   */
  public function getProtocolNumbers(): array
  {
    return $this->protocolNumbers;
  }

  /**
   * @param String[] $protocolNumbers
   */
  public function setProtocolNumbers(array $protocolNumbers)
  {
    $this->protocolNumbers = $protocolNumbers;
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

    //$dto->attachments = self::prepareFileCollection($pratica->getAllegati());

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
    $dto->protocolDcoumentId = $pratica->getIdDocumentoProtocollo();
    $dto->protocolNumbers = null;
    $dto->outcome = $pratica->getEsito();

    //$dto->outcomeMotivation = $pratica->getMotivazioneEsito();
    //$dto->outcomeFile = $pratica->getRispostaOperatore();

    $dto->paymentType = $pratica->getPaymentType();
    $dto->paymentData = self::preparePaymentData($pratica);
    $dto->status = $pratica->getStatus();
    $dto->statusName = strtolower($pratica->getStatusName());

    return $dto;
  }

  public static function decorateDematerializedForms( $data, $attachmentEndpointUrl = '')
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

  public static function decorateDematerializedFormsV2( $data, $attachmentEndpointUrl = '')
  {

    if (!isset($data['flattened'])) {
      return $data;
    }

    $decoratedData = $data['flattened'];
    $keys = array_keys($decoratedData);

    $multiArray = array();

    foreach ($keys as $path) {
      $parts       = explode('.', trim($path, '.'));
      $section     = &$multiArray;
      $sectionName = '';

      $partsCount = count($parts);
      $counter = 0;

      foreach ($parts as $part) {
        $counter ++;
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

  public static function isUploadField ($schema, $field)
  {
    return (isset($schema[$field. '.type']) && $schema[$field. '.type'] == 'file');
  }

  public static function prepareFileCollection( $collection, $attachmentEndpointUrl = '')
  {
    $files = [];
    if ( $collection == null) {
      return $files;
    }
    /** @var Allegato $c */
    foreach ($collection as $c) {
      $files[]= self::prepareFile($c, $attachmentEndpointUrl);
    }
    return $files;
  }

  public static function prepareFormioFile( $files, $attachmentEndpointUrl = '' )
  {
    $result=[];
    foreach ($files as $f) {
      $id = $f['data']['id'];
      $temp['id'] = $id;
      $temp['name'] = $f['name'];
      $temp['url'] = $attachmentEndpointUrl . '/attachments/' .  $id;
      $temp['originalName'] = $f['originalName'];
      $result[]=$temp;
    }
    return $result;
  }

  public static function prepareFile(Allegato $file, $attachmentEndpointUrl = '')
  {
    $temp['id'] = $file->getId();
    $temp['name'] = $file->getName();
    $temp['url'] = $attachmentEndpointUrl . '/attachments/' .  $file->getId();
    $temp['originalName'] = $file->getFilename();
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
    return $entity;
  }

  /**
   * @param Pratica $pratica
   * @return mixed
   */
  public static function preparePaymentData( $pratica ) {
    if (!empty($pratica->getPaymentData())) {
      $gateway = $pratica->getPaymentType();
      /** @var PaymentDataInterface $gatewayClassHandler */
      $gatewayClassHandler = $gateway->getFcqn();


      $paymetdata = $gatewayClassHandler::getSimplifiedData($pratica->getPaymentData());

      return $paymetdata;
    }
    return [];
  }
}
