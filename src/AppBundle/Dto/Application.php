<?php


namespace AppBundle\Dto;

use AppBundle\Entity\Allegato;
use AppBundle\Entity\ModuloCompilato;
use AppBundle\Entity\Pratica;
use AppBundle\Mapper\Giscom\File;
use AppBundle\Mapper\Giscom\FileCollection;
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
   * @SWG\Property(property="compiled_modules")
   * @Serializer\Type("array")
   */
  private $compiledModules;

  /**
   * @var Allegato[]
   * @SWG\Property(property="attachments", type="string")
   * @Serializer\Type("string")
   */
  private $attachments;


  /**
   * @var DateTime
   * @SWG\Property(description="Datetime interval's end date", type="dateTime")
   */
  private $creationTime;


  /**
   * @var DateTime
   * @SWG\Property(description="Datetime interval's end date", type="dateTime")
   */
  private $submissionTime;

  /**
   * @Serializer\Type("string")
   * @SWG\Property(description="Applications's protocol folder number")
   */
  private $protocolFolderNumber;

  /**
   * @Serializer\Type("string")
   * @SWG\Property(description="Applications's protocol number (uuid)")
   */
  private $protocolNumber;

  /**
   * @Serializer\Type("string")
   * @SWG\Property(description="Applications's protocol number (uuid)")
   */
  private $protocolDcoumentId;

  /**
   * @var String[]
   * @SWG\Property(property="protocol_numbers", type="string")
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
   * @SWG\Property(description="Applications's protocol number (uuid)")
   */
  private $outcomeMotivation;

  /**
   * @var Allegato
   * @SWG\Property(property="outcome_file", type="string")
   * @Serializer\Type("string")
   */
  private $outcomeFile;

  /**
   * @Serializer\Type("string")
   * @SWG\Property(description="Applications's protocol number (uuid)")
   */
  private $paymentType;

  /**
   * @var array
   * @SWG\Property(property="payment_data", description="List of payment gateways available for the service and related parameters")
   * @Serializer\Type("array")
   */
  private $paymentData;

  /**
   * @Assert\NotBlank(message="This field is mandatory: name")
   * @Assert\NotNull(message="This field is mandatory: name")
   * @Serializer\Type("integer")
   * @SWG\Property(description="Accepts values: 0 - Hidden, 1 - Pubblished, 2 - Suspended")
   */
  private $status;


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
  public function setId($id): void
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
  public function setUser($user): void
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
  public function setService($service): void
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
  public function setTenant($tenant): void
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
  public function setSubject($subject): void
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
  public function setData(array $data): void
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
  public function setCompiledModules(array $compiledModules): void
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
  public function setAttachments(array $attachments): void
  {
    $this->attachments = $attachments;
  }

  /**
   * @return DateTime
   */
  public function getCreationTime(): DateTime
  {
    return $this->creationTime;
  }

  /**
   * @param DateTime $creationTime
   */
  public function setCreationTime(DateTime $creationTime): void
  {
    $this->creationTime = $creationTime;
  }

  /**
   * @return DateTime
   */
  public function getSubmissionTime(): DateTime
  {
    return $this->submissionTime;
  }

  /**
   * @param DateTime $submissionTime
   */
  public function setSubmissionTime(DateTime $submissionTime): void
  {
    $this->submissionTime = $submissionTime;
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
  public function setProtocolFolderNumber($protocolFolderNumber): void
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
  public function setProtocolNumber($protocolNumber): void
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
  public function setProtocolDcoumentId($protocolDcoumentId): void
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
  public function setProtocolNumbers(array $protocolNumbers): void
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
  public function setOutcome(bool $outcome): void
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
  public function setOutcomeMotivation($outcomeMotivation): void
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
  public function setOutcomeFile(Allegato $outcomeFile): void
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
  public function setPaymentType($paymentType): void
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
  public function setPaymentData(array $paymentData): void
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
  public function setStatus($status): void
  {
    $this->status = $status;
  }

  /**
   * @param Pratica $pratica
   * @return Application
   */
  public static function fromEntity(Pratica $pratica, $attachmentEndpointUrl = '')
  {
    $dto = new self();
    $dto->id = $pratica->getId();
    $dto->user = $pratica->getUser()->getId();
    $dto->tenant = $pratica->getEnte()->getId();
    $dto->service = $pratica->getServizio()->getSlug();
    $dto->subject = $pratica->getOggetto();


    if ($pratica->getServizio()->getPraticaFCQN() == '\AppBundle\Entity\FormIO') {
      $dto->data = self::decorateDematerializedForms($pratica->getDematerializedForms(), $attachmentEndpointUrl);
    } else {
      $dto->data = [];
    }

    $dto->compiledModules = self::prepareFileCollection($pratica->getModuliCompilati(), $attachmentEndpointUrl);
    //$dto->attachments = self::prepareFileCollection($pratica->getAllegati());
    $dto->creationTime = $pratica->getCreationTime();
    $dto->submissionTime = $pratica->getSubmissionTime();
    $dto->protocolFolderNumber = $pratica->getNumeroFascicolo();
    $dto->protocolNumber = $pratica->getNumeroProtocollo();
    $dto->protocolDcoumentId = $pratica->getIdDocumentoProtocollo();
    $dto->protocolNumbers = null;
    $dto->outcome = $pratica->getEsito();

    //$dto->outcomeMotivation = $pratica->getMotivazioneEsito();
    //$dto->outcomeFile = $pratica->getRispostaOperatore();

    $dto->paymentType = $pratica->getPaymentType();
    $dto->paymentData = $pratica->getPaymentData();
    $dto->status = $pratica->getStatus();

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

  public static function isUploadField ($schema, $field)
  {
    return (isset($schema[$field. '.type']) && $schema[$field. '.type'] == 'file');
  }

  public static function prepareFileCollection( $collection, $attachmentEndpointUrl = '')
  {
    $files = [];
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
}
