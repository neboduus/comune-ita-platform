<?php

namespace AppBundle\Entity;

use AppBundle\Model\DefaultProtocolSettings;
use AppBundle\Model\Mailer;
use AppBundle\Model\Webhook;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Translatable\Translatable;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use JMS\Serializer\Annotation as Serializer;

/**
 * @ORM\Entity
 * @ORM\Table(name="ente")
 * @ORM\HasLifecycleCallbacks
 */
class Ente implements Translatable
{

  const NAVIGATION_TYPE_SERVICES = 'services';
  const NAVIGATION_TYPE_CATEGORIES = 'categories';

  /**
   * @ORM\Column(type="guid")
   * @ORM\Id
   */
  protected $id;

  /**
   * @var string
   *
   * @ORM\Column(type="string", length=100)
   * @Gedmo\Translatable
   */
  private $name;

  /**
   * @var string
   *
   * @Gedmo\Slug(fields={"name"}, updatable=false)
   * @ORM\Column(type="string", length=100, unique=true)
   */
  private $slug;

  /**
   * @var string
   * @ORM\Column(type="string", unique=true)
   */
  private $codiceMeccanografico;

  /**
   * @var ArrayCollection
   * @ORM\OneToMany(targetEntity="AppBundle\Entity\OperatoreUser", mappedBy="ente", fetch="EAGER")
   * @Serializer\Exclude()
   */
  private $operatori;

  /**
   * @var ArrayCollection
   * @ORM\OneToMany(targetEntity="AppBundle\Entity\AdminUser", mappedBy="ente", fetch="EAGER")
   * @Serializer\Exclude()
   */
  private $administrators;

  /**
   * @var ArrayCollection
   * @ORM\Column(type="text")
   */
  private $protocolloParameters;

  /**
   * @var string
   * @ORM\Column(type="string", nullable=true)
   */
  private $siteUrl;

  /**
   * @ORM\ManyToMany(targetEntity="AppBundle\Entity\Erogatore", mappedBy="enti")
   * @var Collection;
   * @Serializer\Exclude()
   */
  private $erogatori;

  /**
   * @var string
   * @ORM\Column(type="string", unique=true)
   */
  private $codiceAmministrativo;

  /**
   * @var string
   * @ORM\Column(type="text", nullable=true)
   */
  private $contatti;

  /**
   * @var string
   * @ORM\Column(type="string", nullable=true)
   */
  private $email;

  /**
   * @var string
   * @ORM\Column(type="string", nullable=true)
   */
  private $emailCertificata;

  /**
   * @var string
   * @ORM\Column(type="json", nullable=true)
   * @Gedmo\Translatable
   */
  private $meta;

  /**
   * @var array
   *
   * @ORM\Column(type="json", nullable=true)
   */
  private $gateways;

  /**
   * @var array
   * @ORM\Column(type="json", nullable=true)
   */
  private $backofficeEnabledIntegrations;

  /**
   * @var Mailer[]
   *
   * @ORM\Column(type="json", nullable=true)
   */
  private $mailers;

  /**
   * @var bool
   * @ORM\Column(name="io_enabled", type="boolean", nullable=true)
   */
  private $IOEnabled;

  /**
   * @var bool
   * @ORM\Column(name="linkable_application_meetings", type="boolean", options={"default" : 1})
   */
  private $linkableApplicationMeetings;

  /**
   * @ORM\OneToMany(targetEntity="AppBundle\Entity\Webhook", mappedBy="ente")
   * @var Collection;
   * @Serializer\Exclude()
   */
  private $webhooks;

  /**
   * @var string
   * @ORM\Column(type="string", nullable=true, options={"default" : Ente::NAVIGATION_TYPE_SERVICES})
   */
  private $navigationType = self::NAVIGATION_TYPE_SERVICES;

  /**
   * @Gedmo\Locale
   * Used locale to override Translation listener`s locale
   * this is not a mapped field of entity metadata, just a simple property
   */
  private $locale;


  /**
   * Ente constructor.
   */
  public function __construct()
  {
    $this->id = Uuid::uuid4();
    $this->protocolloParameters = new ArrayCollection();
    $this->operatori = new ArrayCollection();
    $this->administrators = new ArrayCollection();
    $this->gateways = [];
    $this->backofficeEnabledIntegrations = new ArrayCollection();
    $this->mailers = new ArrayCollection();
    $this->webhooks = new ArrayCollection();
    $this->setIOEnabled(false);
    $this->setLinkableApplicationMeetings(true);
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

  public function getNameForEmail()
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
   */
  public function setSlug(string $slug)
  {
    $this->slug = $slug;

    return $this;
  }

  /**
   * @return string
   */
  public function __toString()
  {
    return (string)$this->getId();
  }

  /**
   * @return string
   */
  public function getCodiceMeccanografico()
  {
    return $this->codiceMeccanografico;
  }

  /**
   * @param string $codiceMeccanografico
   * @return Ente
   */
  public function setCodiceMeccanografico($codiceMeccanografico)
  {
    $this->codiceMeccanografico = $codiceMeccanografico;

    return $this;
  }

  /**
   * @param Servizio $servizio
   * @return mixed
   */
  public function getProtocolloParametersPerServizio(Servizio $servizio)
  {
    $this->parseProtocolloParameters();
    if ($this->protocolloParameters->containsKey($servizio->getSlug())) {
      return $this->protocolloParameters->get($servizio->getSlug());
    }

    return null;
  }

  /**
   * @param mixed $protocolloParameters
   * @param Servizio $servizio
   * @return Ente
   */
  public function setProtocolloParametersPerServizio($protocolloParameters, Servizio $servizio)
  {
    $this->parseProtocolloParameters();
    $this->protocolloParameters->set($servizio->getSlug(), $protocolloParameters);

    return $this;
  }

  /**
   * @ORM\PreFlush()
   */
  public function serializeProtocolloParameters()
  {
    if ($this->protocolloParameters instanceof Collection) {
      $this->protocolloParameters = serialize($this->protocolloParameters->toArray());
    }
  }

  /**
   * @ORM\PostLoad()
   * @ORM\PostUpdate()
   */
  public function parseProtocolloParameters()
  {
    if (!$this->protocolloParameters instanceof ArrayCollection) {
      $this->protocolloParameters = new ArrayCollection(unserialize($this->protocolloParameters));
    }
  }

  /**
   * @param Servizio $servizio
   * @return mixed
   */
  public function getDefaultProtocolSettings()
  {
    $this->parseProtocolloParameters();
    if ($this->protocolloParameters->containsKey(DefaultProtocolSettings::KEY)) {
      return $this->protocolloParameters->get(DefaultProtocolSettings::KEY);
    }

    return null;
  }

  /**
   * @param $settings
   * @return Ente
   */
  public function setDefaultProtocolSettings($settings)
  {
    $this->parseProtocolloParameters();
    $this->protocolloParameters->set(DefaultProtocolSettings::KEY, $settings);

    return $this;
  }


  /**
   * @return string
   */
  public function getSiteUrl()
  {
    return $this->siteUrl;
  }

  /**
   * @param string $siteUrl
   *
   * @return Ente
   */
  public function setSiteUrl($siteUrl)
  {
    $this->siteUrl = $siteUrl;

    return $this;
  }

  /**
   * @return Collection
   */
  public function getOperatori(): Collection
  {
    return $this->operatori;
  }

  /**
   * @return Collection
   */
  public function getAdministrators(): Collection
  {
    return $this->administrators;
  }

  /**
   * @return Collection
   */
  public function getErogatori(): Collection
  {
    return $this->erogatori;
  }

  /**
   * @return string
   */
  public function getCodiceAmministrativo()
  {
    return $this->codiceAmministrativo;
  }

  /**
   * @param string $codiceAmministrativo
   *
   * @return Ente
   */
  public function setCodiceAmministrativo($codiceAmministrativo)
  {
    $this->codiceAmministrativo = $codiceAmministrativo;

    return $this;
  }

  /**
   * @return string
   */
  public function getContatti()
  {
    return $this->contatti;
  }

  /**
   * @param string $contatti
   */
  public function setContatti(string $contatti)
  {
    $this->contatti = $contatti;
    return $this;
  }

  /**
   * @return string
   */
  public function getEmail()
  {
    return $this->email;
  }

  /**
   * @param string $email
   */
  public function setEmail(string $email)
  {
    $this->email = $email;
    return $this;
  }

  /**
   * @return string
   */
  public function getEmailCertificata()
  {
    return $this->emailCertificata;
  }

  /**
   * @param string $emailCertificata
   * @return $this
   */
  public function setEmailCertificata(string $emailCertificata)
  {
    $this->emailCertificata = $emailCertificata;
    return $this;
  }

  /**
   * @return ArrayCollection
   */
  public function getProtocolloParameters(): ArrayCollection
  {
    $this->parseProtocolloParameters();
    return $this->protocolloParameters;
  }

  /**
   * @return string
   */
  public function getMeta()
  {
    return $this->meta;
  }

  /**
   * @param bool $attribute
   * @return mixed
   */
  public function getMetaAsArray($attribute = false)
  {
    if (!is_array($this->meta)) {
      $meta = \json_decode($this->meta, 1);
    } else {
      $meta = $this->meta;
    }

    if ($attribute) {
      if (isset($meta[$attribute])) {
        return $meta[$attribute];
      }
      return null;
    }
    return $meta;
  }

  /**
   * @param string $meta
   * @return $this
   */
  public function setMeta(string $meta)
  {
    $this->meta = $meta;
    return $this;
  }

  /**
   * @return Collection
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
   * @param Collection $gateways
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
  public function setBackofficeEnabledIntegrations(array $backofficeEnabledIntegrations)
  {
    $this->backofficeEnabledIntegrations = $backofficeEnabledIntegrations;
    return $this;
  }

  /**
   * @return Mailer[]|null
   */
  public function getMailers()
  {
    if ($this->mailers != null) {
      $mailers = [];
      foreach ($this->mailers as $k => $data) {
        $mailers[$k] = Mailer::fromArray($data);
      }
      return $mailers;
    }
    return null;
  }

  /**
   * @return Mailer|null
   */
  public function getMailer($identifier)
  {
    if (isset($this->mailers[$identifier])) {
      return Mailer::fromArray($this->mailers[$identifier]);
    }
    return null;
  }

  /**
   * @param ArrayCollection $mailers
   * @return $this
   */
  public function setMailers($mailers)
  {
    $tmp = [];
    /** @var Mailer $m */
    foreach ($mailers as $m) {
      $tmp[\md5($m->getTitle())] = $m;
    }
    $this->mailers = $tmp;
    return $this;
  }

  /**
   * @return Collection
   */
  public function getWebhooks(): Collection
  {
    return $this->webhooks;
  }

  /**
   * @param Collection $webhooks
   */
  public function setWebhooks(Collection $webhooks): void
  {
    $this->webhooks = $webhooks;
  }

  /**
   * @return bool
   */
  public function isIOEnabled(): ?bool
  {
    return $this->IOEnabled;
  }

  /**
   * @param bool $IOEnabled
   */
  public function setIOEnabled(?bool $IOEnabled)
  {
    $this->IOEnabled = $IOEnabled;
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
   * @return string
   */
  public function getNavigationType(): ?string
  {
    return $this->navigationType;
  }

  /**
   * @param string $navigationType
   */
  public function setNavigationType(?string $navigationType): void
  {
    $this->navigationType = $navigationType;
  }

  public function setTranslatableLocale($locale)
  {
    $this->locale = $locale;
  }

  public static function fromEntity(Ente $ente)
  {
    $dto = new self();
    $dto->id = $ente->getId();
    $dto->name = $ente->getName();
    $dto->slug = $ente->getSlug();
    $dto->meta = json_decode($ente->getMeta(), true);
    $dto->codiceMeccanografico = $ente->getCodiceMeccanografico();
    $dto->protocolloParameters = null;
    $dto->siteUrl = $ente->getSiteUrl();
    $dto->codiceAmministrativo = $ente->getCodiceAmministrativo();
    $dto->contatti = $ente->getContatti();
    $dto->email = $ente->getEmail();
    $dto->emailCertificata = $ente->getEmailCertificata();
    $dto->gateways = [];
    $dto->backofficeEnabledIntegrations = [];
    $dto->mailers = [];

    return $dto;
  }

}
