<?php


namespace AppBundle\Dto;


use AppBundle\BackOffice\CalendarsBackOffice;
use AppBundle\Entity\Ente as TenantEntity;
use AppBundle\Model\Gateway;
use JMS\Serializer\Annotation as Serializer;
use JMS\Serializer\Annotation\Groups;
use Swagger\Annotations as SWG;
use Nelmio\ApiDocBundle\Annotation\Model;
use Symfony\Component\Validator\Constraints as Assert;

class Tenant
{

  /**
   * @Serializer\Type("string")
   * @SWG\Property(description="Message's uuid", type="string")
   * @Groups({"read"})
   */
  private $id;


  /**
   * @Assert\NotBlank(message="Mechanographic code is mandatory")
   * @Assert\NotNull(message="Mechanographic code is mandatory")
   * @Serializer\Type("string")
   * @SWG\Property(description="Tenant mechanographic code")
   * @Groups({"read", "write"})
   */
  private $mechanographicCode;

  /**
   * @Assert\NotBlank(message="Administrative code is mandatory")
   * @Assert\NotNull(message="Administrative code is mandatory")
   * @Serializer\Type("string")
   * @SWG\Property(description="Tenant administrative code")
   * @Groups({"read", "write"})
   */
  private $administrativeCode;

  /**
   * @Assert\NotBlank(message="Site url is mandatory")
   * @Assert\NotNull(message="Site url is mandatory")
   * @Serializer\Type("string")
   * @SWG\Property(description="Link on the title of each page")
   * @Groups({"read", "write"})
   */
  private $siteUrl;

  /**
   * @Serializer\Type("object")
   * @SWG\Property(description="Tenant graphic appearance")
   * @Groups({"read", "write"})
   */
  private $meta;

  /**
   * @Serializer\Type("bool")
   * @SWG\Property(description="Enable App IO integration")
   * @Groups({"read", "write"})
   */
  private $ioEnabled;

  /**
   * @Serializer\Type("array")
   * @SWG\Property(property="gateways", description="List of payment gateways and related parameters", type="array", @SWG\Items(type="object", ref=@Model(type=Gateway::class)))
   * @Groups({"read", "write"})
   */
  private $gateways;

  /**
   * @Serializer\Type("array")
   * @SWG\Property(property="backoffice_enabled_integrations", description="List of backoffices, available options are 'operatori_subscription-service_index', 'operatori_calendars_index'", type="array", @SWG\Items(type="string"))
   * @Groups({"read", "write"})
   */
  private $backofficeEnabledIntegrations;

  /**
   * @Serializer\Type("bool")
   * @SWG\Property(description="Enable linkable application meetings")
   * @Groups({"read", "write"})
   */
  private $linkableApplicationMeetings;


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
  public function getMechanographicCode()
  {
    return $this->mechanographicCode;
  }

  /**
   * @param mixed $mechanographicCode
   */
  public function setMechanographicCode($mechanographicCode): void
  {
    $this->mechanographicCode = $mechanographicCode;
  }

  /**
   * @return mixed
   */
  public function getAdministrativeCode()
  {
    return $this->administrativeCode;
  }

  /**
   * @param mixed $administrativeCode
   */
  public function setAdministrativeCode($administrativeCode): void
  {
    $this->administrativeCode = $administrativeCode;
  }

  /**
   * @return mixed
   */
  public function getSiteUrl()
  {
    return $this->siteUrl;
  }

  /**
   * @param mixed $siteUrl
   */
  public function setSiteUrl($siteUrl): void
  {
    $this->siteUrl = $siteUrl;
  }

  /**
   * @return mixed
   */
  public function getMeta()
  {
    return $this->meta;
  }

  /**
   * @param mixed $meta
   */
  public function setMeta($meta): void
  {
    $this->meta = $meta;
  }

  /**
   * @return bool
   */
  public function isIoEnabled(): ?bool
  {
    return $this->ioEnabled;
  }

  /**
   * @param bool $ioEnabled
   */
  public function setIoEnabled(?bool $ioEnabled)
  {
    $this->ioEnabled = $ioEnabled;
  }

  /**
   * @return bool
   */
  public function isLinkableApplicationMeetings(): ?bool
  {
    return $this->linkableApplicationMeetings;
  }

  /**
   * @param bool $linkableApplicationMeetings
   */
  public function setLinkableApplicationMeetings(?bool $linkableApplicationMeetings)
  {
    $this->linkableApplicationMeetings = $linkableApplicationMeetings;
  }

  /**
   * @return array
   */
  public function getGateways()
  {
    if (is_array($this->gateways)) {
      return $this->gateways;
    } else {
      return json_decode($this->gateways);
    }
  }

  /**
   * @param Gateway[] $gateways
   * @return $this
   */
  public function setGateways($gateways)
  {
    $this->gateways = $gateways;
    return $this;
  }

  /**
   * @return array
   */
  public function getBackofficeEnabledIntegrations()
  {
    return $this->backofficeEnabledIntegrations;
  }

  /**
   * @param array $backofficeEnabledIntegrations
   * @return $this
   */
  public function setBackofficeEnabledIntegrations($backofficeEnabledIntegrations)
  {
    $this->backofficeEnabledIntegrations = $backofficeEnabledIntegrations;
    return $this;
  }

  /**
   * @param TenantEntity $tenant
   * @return Tenant
   */
  public static function fromEntity(TenantEntity $tenant)
  {
    $dto = new self();
    $dto->id = $tenant->getId();
    $dto->mechanographicCode = $tenant->getCodiceMeccanografico();
    $dto->administrativeCode = $tenant->getCodiceAmministrativo();
    $dto->siteUrl = $tenant->getSiteUrl();
    $dto->meta = $tenant->getMeta();
    $dto->ioEnabled = $tenant->isIOEnabled();
    $dto->gateways = [];
    $dto->backofficeEnabledIntegrations = $tenant->getBackofficeEnabledIntegrations();
    $dto->linkableApplicationMeetings = $tenant->isLinkableApplicationMeetings();

    foreach ($tenant->getGateways() as $gateway) {
      $g = new Gateway();
      $g->setIdentifier($gateway['identifier']);
      $g->setParameters($gateway['parameters']);
      $dto->gateways[] = $g;
    }

    return $dto;
  }

  /**
   * @param TenantEntity|null $entity
   * @return TenantEntity
   */
  public function toEntity(TenantEntity $entity = null)
  {
    if (!$entity) {
      $entity = new TenantEntity();
    }

    $entity->setCodiceMeccanografico($this->mechanographicCode);
    $entity->setCodiceAmministrativo($this->administrativeCode);
    $entity->setSiteUrl($this->siteUrl);
    $entity->setMeta($this->meta);
    $entity->setIOEnabled($this->ioEnabled);
    $entity->setGateways($this->gateways);
    $entity->setBackofficeEnabledIntegrations($this->backofficeEnabledIntegrations);

    if (!in_array(CalendarsBackOffice::PATH, $entity->getBackofficeEnabledIntegrations())) {
      // disable if integration is not set
      $entity->setLinkableApplicationMeetings(false);
    }

    return $entity;
  }

  /**
   * @param $data
   * @return mixed
   */
  public static function normalizeData($data)
  {

    // Todo: find better way
    if (isset($data['gateways']) && count($data['gateways']) > 0) {
      $temp = [];
      foreach ($data['gateways'] as $f) {
        $f['parameters'] = \json_encode($f['parameters']);
        $temp[$f['identifier']]= $f;
      }
      $data['gateways'] = $temp;
    }

    // Todo: find better way
    if (isset($data['meta'])) {
      $data['meta'] = \json_encode($data['meta']);
    }
    return $data;
  }

}
