<?php

namespace App\Entity;

use App\Dto\UserAuthenticationData;
use App\Model\Transition;
use App\Utils\StringUtils;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Entity\PraticaRepository")
 * @ORM\Table(name="pratica")
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="type", type="string")
 * @ORM\DiscriminatorMap({"default" = "Pratica",
 *     "certificato_nascita" = "CertificatoNascita",
 *     "occupazione_suolo_pubblico" = "OccupazioneSuoloPubblico",
 *     "iscrizione_registro_associazioni" = "IscrizioneRegistroAssociazioni",
 *     "scia_pratica_edilizia" = "SciaPraticaEdilizia",
 *     "comunicazione_opere_libere" = "ComunicazioneOpereLibere",
 *     "comunicazione_inizio_lavori_asseverata" = "ComunicazioneInizioLavoriAsseverata",
 *     "domanda_di_permesso_di_costruire" = "DomandaDiPermessoDiCostruire",
 *     "domanda_di_permesso_di_costruire_in_sanatoria" = "DomandaDiPermessoDiCostruireInSanatoria",
 *     "comunicazione_inizio_lavori" = "ComunicazioneInizioLavori",
 *     "dichiarazione_ultimazione_lavori" = "DichiarazioneUltimazioneLavori",
 *     "autorizzazione_paesaggistica_sindaco" = "AutorizzazionePaesaggisticaSindaco",
 *     "segnalazione_certificata_agibilita" = "SegnalazioneCertificataAgibilita",
 *     "estratto_nascita" = "EstrattoNascita",
 *     "certificato_morte" = "CertificatoMorte",
 *     "estratto_morte" = "EstrattoMorte",
 *     "certificato_matrimonio" = "CertificatoMatrimonio",
 *     "estratto_matrimonio" = "EstrattoMatrimonio",
 *     "form_io" = "FormIO",
 *      "built_in" = "BuiltIn"
 * })
 * @ORM\HasLifecycleCallbacks
 */
class Pratica implements IntegrabileInterface, PaymentPracticeInterface
{
  const STATUS_DRAFT = 1000;

  const STATUS_PAYMENT_PENDING = 1500;
  const STATUS_PAYMENT_OUTCOME_PENDING = 1510;
  const STATUS_PAYMENT_SUCCESS = 1520;
  const STATUS_PAYMENT_ERROR = 1530;

  const STATUS_PRE_SUBMIT = 1900;

  const STATUS_SUBMITTED = 2000;
  const STATUS_REGISTERED = 3000;
  const STATUS_PENDING = 4000;

  const STATUS_REQUEST_INTEGRATION = 4100;
  const STATUS_DRAFT_FOR_INTEGRATION = 4200;
  const STATUS_SUBMITTED_AFTER_INTEGRATION = 4300;
  const STATUS_REGISTERED_AFTER_INTEGRATION = 4400;
  const STATUS_PENDING_AFTER_INTEGRATION = 4500;

  const STATUS_PROCESSING = 5000;

  const STATUS_COMPLETE_WAITALLEGATIOPERATORE = 6000;
  const STATUS_COMPLETE = 7000;

  const STATUS_CANCELLED_WAITALLEGATIOPERATORE = 8000;
  const STATUS_CANCELLED = 9000;
  const STATUS_WITHDRAW = 20000;

  const STATUS_REVOKED = 50000;

  const ALLOWED_MANUAL_CHANGE_STATES = [
    Pratica::STATUS_PAYMENT_PENDING,
    Pratica::STATUS_SUBMITTED,
    Pratica::STATUS_REGISTERED,
    Pratica::STATUS_PENDING,
    Pratica::STATUS_REVOKED,
  ];

  const FINAL_STATES = [
    Pratica::STATUS_COMPLETE,
    Pratica::STATUS_CANCELLED,
    Pratica::STATUS_WITHDRAW,
  ];

  const ACCEPTED = true;
  const REJECTED = false;

  const TYPE_DEFAULT = "default";
  const TYPE_CERTIFICATO_NASCITA = "certificato_nascita";

  const TYPE_SCIA_PRATICA_EDILIZIA = "scia_pratica_edilizia";
  const TYPE_COMUNICAZIONE_OPERE_LIBERE = "comunicazione_opere_libere";
  const TYPE_COMUNICAZIONE_INIZIO_LAVORI_ASSEVERATA = 'comunicazione_inizio_lavori_asseverata';
  const TYPE_DOMANDA_DI_PERMESSO_DI_COSTRUIRE = 'domanda_di_permesso_di_costruire';
  const TYPE_DOMANDA_DI_PERMESSO_DI_COSTRUIRE_IN_SANATORIA = 'domanda_di_permesso_di_costruire_in_sanatoria';
  const TYPE_COMUNICAZIONE_INIZIO_LAVORI = 'comunicazione_inizio_lavori';
  const TYPE_DICHIARAZIONE_ULTIMAZIONE_LAVORI = 'dichiarazione_ultimazione_lavori';
  const TYPE_AUTORIZZAZIONE_PAESAGGISTICA_SINDACO = 'autorizzazione_paesaggistica_sindaco';
  const TYPE_SEGNALAZIONE_CERTIFICATA_AGIBILITA = 'segnalazione_certificata_agibilita';

  const TYPE_OCCUPAZIONE_SUOLO_PUBBLICO = "occupazione_suolo_pubblico";
  const TYPE_ISCRIZIONE_REGISTRO_ASSOCIAZIONI = "iscrizione_registro_associazioni";

  const TYPE_ESTRATTO_NASCITA = "estratto_nascita";
  const TYPE_CERTIFICATO_MORTE = "certificato_morte";
  const TYPE_ESTRATTO_MORTE = "estratto_morte";
  const TYPE_CERTIFICATO_MATRIMONIO = "certificato_matrimonio";
  const TYPE_ESTRATTO_MATRIMONIO = "estratto_matrimonio";

  const TYPE_FORMIO = 'form_io';
  const TYPE_BUILTIN = 'built_in';

  const TIPO_DELEGA_DELEGATO = 'delegato';
  const TIPO_DELEGA_INCARICATO = 'incaricato';
  const TIPO_DELEGA_ALTRO = 'altro';

  const HASH_SESSION_KEY = 'anonymous-hash';

  use TimestampableEntity;

  /**
   * @var string
   */
  protected $type;

  /**
   * @ORM\Column(type="guid")
   * @ORM\Id
   */
  private $id;

  /**
   * @ORM\ManyToOne(targetEntity="App\Entity\User")
   * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false)
   */
  private $user;

  /**
   * @ORM\Column(type="json", nullable=true)
   */
  private $authenticationData;

  /**
   * @ORM\ManyToOne(targetEntity="App\Entity\UserSession")
   * @ORM\JoinColumn(name="session_data_id", referencedColumnName="id", nullable=true)
   */
  private $sessionData;

  /**
   * @ORM\ManyToOne(targetEntity="Servizio")
   * @ORM\JoinColumn(name="servizio_id", referencedColumnName="id", nullable=false)
   */
  private $servizio;

  /**
   * @ORM\ManyToOne(targetEntity="Erogatore")
   * @ORM\JoinColumn(name="erogatore_id", referencedColumnName="id", nullable=true)
   */
  private $erogatore;

  /**
   * @ORM\ManyToOne(targetEntity="Ente")
   * @ORM\JoinColumn(name="ente_id", referencedColumnName="id", nullable=true)
   */
  private $ente;

  /**
   * @ORM\ManyToOne(targetEntity="App\Entity\OperatoreUser")
   * @ORM\JoinColumn(name="operatore_id", referencedColumnName="id", nullable=true)
   */
  private $operatore;

  /**
   * @ORM\Column(type="text", nullable=true)
   * @var string
   */
  private $oggetto;

  /**
   * @ORM\ManyToMany(targetEntity="App\Entity\Allegato", inversedBy="pratiche", orphanRemoval=false)
   * @var ArrayCollection
   * @Assert\Valid(traverse=true)
   */
  private $allegati;

  /**
   * @ORM\ManyToMany(targetEntity="App\Entity\ModuloCompilato", inversedBy="pratiche2", orphanRemoval=false)
   * @var ArrayCollection
   * @Assert\Valid(traverse=true)
   */
  private $moduliCompilati;

  /**
   * @ORM\ManyToMany(targetEntity="App\Entity\AllegatoOperatore", inversedBy="pratiche3", orphanRemoval=false)
   * @var ArrayCollection
   * @Assert\Valid(traverse=true)
   */
  private $allegatiOperatore;

  /**
   * @ORM\OneToMany(targetEntity="App\Entity\Message", mappedBy="application")
   * @ORM\OrderBy({"createdAt" = "ASC"})
   * @var ArrayCollection
   */
  private $messages;


  /**
   * @ORM\OneToOne(targetEntity="App\Entity\RispostaOperatore", orphanRemoval=false)
   * @ORM\JoinColumn(nullable=true)
   * @var RispostaOperatore
   */
  private $rispostaOperatore;

  /**
   * @ORM\Column(type="integer", name="creation_time")
   */
  private $creationTime;

  /**
   * @ORM\Column(type="integer", name="submission_time", nullable=true)
   */
  private $submissionTime;

  /**
   * @ORM\Column(type="integer")
   */
  private $status;

  /**
   * @ORM\Column(type="string", nullable=true)
   * @var string
   */
  private $numeroFascicolo;

  /**
   * @ORM\Column(type="string", nullable=true)
   * @var string
   */
  private $codiceFascicolo;

  /**
   * @ORM\Column(type="string", nullable=true)
   * @var string
   */
  private $numeroProtocollo;

  /**
   * @ORM\Column(type="string", nullable=true)
   * @var string
   */
  private $idDocumentoProtocollo;

  /**
   * @ORM\Column(type="array", nullable=true)
   * @var ArrayCollection
   */
  private $numeriProtocollo;

  /**
   * @ORM\Column(type="integer", name="protocol_time", nullable=true)
   */
  private $protocolTime;

  /**
   * @var string
   * @ORM\Column(type="text", nullable=true)
   */
  private $data;

  /**
   * @var string
   */
  private $statusName;

  /**
   * @var int
   * @ORM\Column(type="integer", nullable=true)
   */
  private $latestStatusChangeTimestamp;

  /**
   * @var int
   * @ORM\Column(type="integer", nullable=true)
   */
  private $latestCPSCommunicationTimestamp;

  /**
   * @var int
   * @ORM\Column(type="integer", nullable=true)
   */
  private $latestOperatoreCommunicationTimestamp;

  /**
   * @var Collection
   * @ORM\Column(type="text", nullable=true)
   */
  private $storicoStati;

  /**
   * @var string
   * @ORM\Column(type="string", nullable=true)
   */
  private $richiedenteNome;

  /**
   * @var string
   * @ORM\Column(type="string", nullable=true)
   */
  private $richiedenteCognome;

  /**
   * @var string
   * @ORM\Column(type="string", nullable=true)
   */
  private $richiedenteCodiceFiscale;

  /**
   * @var string
   * @ORM\Column(type="string", nullable=true)
   */
  private $richiedenteLuogoNascita;

  /**
   * @var \DateTime
   * @ORM\Column(type="datetime", nullable=true)
   */
  private $richiedenteDataNascita;

  /**
   * @var string
   * @ORM\Column(type="string", nullable=true)
   */
  private $richiedenteIndirizzoResidenza;

  /**
   * @var string
   * @ORM\Column(type="string", length=10, nullable=true)
   */
  private $richiedenteCapResidenza;

  /**
   * @var string
   * @ORM\Column(type="string", nullable=true)
   */
  private $richiedenteCittaResidenza;

  /**
   * @var string
   * @ORM\Column(type="string", nullable=true)
   */
  private $richiedenteTelefono;

  /**
   * @var string
   * @ORM\Column(type="string", nullable=true)
   */
  private $richiedenteEmail;

  /**
   * @var bool
   * @ORM\Column(type="boolean", nullable=true)
   */
  private $accettoIstruzioni;


  /**
   * @var int
   * @ORM\Column(type="integer", nullable=false)
   */
  private $lastCompiledStep;

  /**
   * @var string
   * @ORM\Column(type="string",nullable=true)
   */
  private $instanceId;

  /**
   * @var bool
   * @ORM\Column(type="boolean", nullable=true)
   */
  private $esito;

  /**
   * @var string
   * @ORM\Column(type="text", nullable=true)
   */
  private $motivazioneEsito;

  /**
   * @ORM\Column(type="text", nullable=true)
   * @var string $userCompilationNotes
   */
  private $userCompilationNotes;

  /**
   * @ORM\OneToMany(targetEntity="App\Entity\RichiestaIntegrazione", mappedBy="praticaPerCuiServeIntegrazione", orphanRemoval=false)
   * @ORM\OrderBy({"createdAt" = "ASC"})
   * @var ArrayCollection
   */
  private $richiesteIntegrazione;

  /**
   * @ORM\Column(type="string", nullable=true)
   */
  private $delegaType;

  /**
   * @ORM\Column(type="json", nullable=true)
   * @var $paymentData array
   */
  private $delegaData;

  /**
   * @ORM\Column(type="json", options={"jsonb":true}, nullable=true)
   * @var $relatedCFs array
   */
  private $relatedCFs;

  /**
   * @ORM\Column(type="string", nullable=true)
   */
  private $paymentType;

  /**
   * @ORM\Column(type="json", nullable=true)
   * @var $paymentData array
   */
  private $paymentData;


  private $tipiDelega;

  /**
   * @ORM\Column(type="string", nullable=true)
   * @var string
   */
  private $hash;

  /**
   * @ORM\OneToMany(targetEntity="Pratica", mappedBy="parent", fetch="EXTRA_LAZY")
   */
  private $children;

  /**
   * @ORM\ManyToOne(targetEntity="Pratica", inversedBy="children")
   * @ORM\JoinColumn(name="parent_id", referencedColumnName="id")
   */
  private $parent;

  /**
   * @ORM\ManyToOne(targetEntity="App\Entity\ServiceGroup", inversedBy="applications")
   * @ORM\JoinColumn(name="service_group_id", referencedColumnName="id", nullable=true)
   */
  private $serviceGroup;

  /**
   * @ORM\Column(type="guid", nullable=true)
   */
  private $folderId;

  /**
   * @ORM\ManyToMany (targetEntity="App\Entity\Meeting", inversedBy="applications")
   * @ORM\JoinTable(name="application_meetings",
   *      joinColumns={@ORM\JoinColumn(name="application_id", referencedColumnName="id", onDelete="CASCADE")},
   *      inverseJoinColumns={@ORM\JoinColumn(name="meeting_id", referencedColumnName="id", unique=true, onDelete="CASCADE")}
   *      )
   * @var ArrayCollection
   */
  private $meetings;

  /**
   * @ORM\Column(type="json", nullable=true, options={"jsonb":true})
   * @var $backofficeFormData
   */
  private $backofficeFormData;

  /**
   * @ORM\Column(type="string", nullable=true)
   * @var string
   */
  private $locale = 'it';


  /**
   * Pratica constructor.
   */
  public function __construct()
  {
    if (!$this->id) {
      $this->id = Uuid::uuid4();
    }
    $this->creationTime = time();
    $this->type = self::TYPE_DEFAULT;
    $this->numeroFascicolo = null;
    $this->codiceFascicolo = null;
    $this->numeriProtocollo = new ArrayCollection();
    $this->allegati = new ArrayCollection();
    $this->moduliCompilati = new ArrayCollection();
    $this->allegatiOperatore = new ArrayCollection();
    $this->messages = new ArrayCollection();
    $this->latestStatusChangeTimestamp = $this->latestCPSCommunicationTimestamp = $this->latestOperatoreCommunicationTimestamp = -10000000;
    $this->storicoStati = new ArrayCollection();
    $this->lastCompiledStep = 0;
    $this->richiesteIntegrazione = new ArrayCollection();
    $this->children = new ArrayCollection();
    $this->meetings = new ArrayCollection();
  }

  public function __clone()
  {
    $this->id = Uuid::uuid4();
    $this->numeroProtocollo = null;
    $this->numeroFascicolo = null;
    $this->codiceFascicolo = null;
    $this->idDocumentoProtocollo = null;
    $this->numeriProtocollo = new ArrayCollection();
    $this->moduliCompilati = new ArrayCollection();
    $this->lastCompiledStep = 0;
  }

  /**
   * @return User|CPSUser
   */
  public function getUser()
  {
    return $this->user;
  }

  /**
   * @param User $user
   *
   * @return $this
   */
  public function setUser(User $user)
  {
    $this->user = $user;

    return $this;
  }

  /**
   * @return UserAuthenticationData
   */
  public function getAuthenticationData()
  {
    $data = $this->authenticationData;
    if (is_string($data)) {
      $data = (array)json_decode($data, true);
    }

    return UserAuthenticationData::fromArray((array)$data);
  }

  /**
   * @param UserAuthenticationData $authenticationData
   *
   * @return $this
   */
  public function setAuthenticationData(UserAuthenticationData $authenticationData)
  {
    $this->authenticationData = $authenticationData;

    return $this;
  }

  /**
   * @return UserSession
   */
  public function getSessionData()
  {
    return $this->sessionData;
  }

  /**
   * @param UserSession $sessionData
   * @return Pratica
   */
  public function setSessionData($sessionData)
  {
    $this->sessionData = $sessionData;

    return $this;
  }

  /**
   * @return Servizio
   */
  public function getServizio()
  {
    return $this->servizio;
  }

  /**
   * @param Servizio $servizio
   *
   * @return $this
   */
  public function setServizio(Servizio $servizio)
  {
    $this->servizio = $servizio;

    return $this;
  }

  /**
   * @return mixed
   */
  public function getCreationTime()
  {
    return $this->creationTime;
  }

  /**
   * @param integer $time
   *
   * @return $this
   */
  public function setCreationTime($time)
  {
    $this->creationTime = $time;

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
   * @return string
   */
  public function getStatusName()
  {
    $class = new \ReflectionClass(__CLASS__);
    $constants = $class->getConstants();
    foreach ($constants as $name => $value) {
      if ($value == $this->status) {
        return $name;
      }
    }

    return null;
  }

  public static function getStatusNameByCode($code)
  {
    $class = new \ReflectionClass(__CLASS__);
    $constants = $class->getConstants();

    foreach ($constants as $name => $value) {
      if ($value == $code) {
        return $name;
      }
    }

    return '';
  }

  public static function getStatusCodeByName($statusName)
  {
    $class = new \ReflectionClass(__CLASS__);
    $constants = $class->getConstants();

    foreach ($constants as $name => $code) {
      if (strtolower($name) === strtolower($statusName)) {
        return $code;
      }
    }

    return '';
  }

  public static function getStatuses()
  {
    $statuses = [];
    $class = new \ReflectionClass(__CLASS__);
    $constants = $class->getConstants();
    foreach ($constants as $name => $value) {
      if (strpos($name, 'STATUS_') === 0) {
        $statuses[$value] = [
          'id' => $value,
          'identifier' => $name,
        ];
      }
    }

    return $statuses;
  }


  /**
   * @param $status
   * @param StatusChange|null $statusChange
   *
   * @return $this
   */
  public function setStatus($status, StatusChange $statusChange = null)
  {
    $this->status = $status;
    $this->latestStatusChangeTimestamp = time();
    $timestamp = $this->latestStatusChangeTimestamp;

    if ($statusChange != null) {
      $timestamp = $statusChange->getTimestamp();
    }
    $updated = null;

    $newStatus = [$status, $statusChange ? $statusChange->toArray() : null];

    if ($this->getStoricoStati()->containsKey($timestamp)) {
      $updated = $this->getStoricoStati()->get($timestamp);
      $updated[] = $newStatus;
    } else {
      $updated = [$newStatus];
    }
    $this->storicoStati->set($timestamp, $updated);

    return $this;
  }

  /**
   * @return Erogatore
   */
  public function getErogatore()
  {
    return $this->erogatore;
  }

  /**
   * @param Erogatore $erogatore
   *
   * @return $this
   */
  public function setErogatore(Erogatore $erogatore)
  {
    $this->erogatore = $erogatore;

    return $this;
  }

  /**
   * @return string
   */
  public function getType()
  {
    return $this->type;
  }

  /**
   * @param mixed $type
   *
   * @return static
   */
  public function setType($type)
  {
    $this->type = $type;

    return $this;
  }

  /**
   * @return OperatoreUser|null
   */
  public function getOperatore()
  {
    return $this->operatore;
  }

  /**
   * @param OperatoreUser|null $operatore
   *
   * @return Pratica
   */
  public function setOperatore(?OperatoreUser $operatore)
  {
    $this->operatore = $operatore;

    return $this;
  }

  /**
   * @return string
   */
  public function getData()
  {
    return $this->data;
  }

  /**
   * @param string $data
   *
   * @return Pratica
   */
  public function setData($data)
  {
    $this->data = $data;

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
   * @return UuidInterface
   */
  public function getId()
  {
    return $this->id;
  }

  /**
   * @param Uuid $id
   * @return $this
   */
  public function setId(Uuid $id)
  {
    $this->id = $id;

    return $this;
  }

  /**
   * @return mixed
   */
  public function getNumeroFascicolo()
  {
    return $this->numeroFascicolo;
  }

  /**
   * @param string $numeroFascicolo
   *
   * @return $this
   */
  public function setNumeroFascicolo($numeroFascicolo)
  {
    $this->numeroFascicolo = $numeroFascicolo;

    return $this;
  }

  /**
   * @return mixed
   */
  public function getCodiceFascicolo()
  {
    return $this->codiceFascicolo;
  }

  /**
   * @param string $codiceFascicolo
   *
   * @return $this
   */
  public function setCodiceFascicolo($codiceFascicolo)
  {
    $this->codiceFascicolo = $codiceFascicolo;

    return $this;
  }

  /**
   * @return string
   */
  public function getIdDocumentoProtocollo()
  {
    return $this->idDocumentoProtocollo;
  }

  /**
   * @param string $idDocumentoProtocollo
   *
   * @return Pratica
   */
  public function setIdDocumentoProtocollo($idDocumentoProtocollo)
  {
    $this->idDocumentoProtocollo = $idDocumentoProtocollo;

    return $this;
  }

  /**
   * @param mixed $numeroDiProtocollo
   *
   * @return Pratica
   */
  public function addNumeroDiProtocollo($numeroDiProtocollo)
  {
    if (!$this->numeriProtocollo instanceof ArrayCollection) {
      $this->jsonToArray();
    }
    if (!$this->numeriProtocollo->contains($numeroDiProtocollo)) {
      $this->numeriProtocollo->add($numeroDiProtocollo);
    }

    return $this;
  }

  /**
   * @param ArrayCollection $numeriProtocollo
   */
  public function setNumeriProtocollo($numeriProtocollo)
  {
    $this->numeriProtocollo = $numeriProtocollo;
  }

  /**
   * @return mixed
   */
  public function getProtocolTime()
  {
    return $this->protocolTime;
  }

  /**
   * @param integer $time
   *
   * @return $this
   */
  public function setProtocolTime($time)
  {
    $this->protocolTime = $time;

    return $this;
  }

  /**
   * @return Collection
   */
  public function getAllegati()
  {
    return $this->allegati;
  }

  /**
   * @return array
   */
  public function getAllegatiWithIndex()
  {
    $allegati = [];
    /** @var Allegato $a */
    foreach ($this->allegati as $a) {
      $allegati[$a->getId()] = $a;
    }

    return $allegati;
  }

  /**
   * @param Allegato $allegato
   *
   * @return $this
   */
  public function addAllegato(Allegato $allegato)
  {
    if (!$this->allegati->contains($allegato)) {
      $this->allegati->add($allegato);
      $allegato->addPratica($this);
    }

    return $this;
  }

  /**
   * @param Allegato $allegato
   *
   * @return $this
   */
  public function removeAllegato(Allegato $allegato)
  {
    if ($this->allegati->contains($allegato)) {
      $this->allegati->removeElement($allegato);
      $allegato->removePratica($this);
    }

    return $this;
  }

  /**
   * @param ModuloCompilato $modulo
   * @return $this
   */
  public function removeModuloCompilato(ModuloCompilato $modulo)
  {
    if ($this->moduliCompilati->contains($modulo)) {
      $this->moduliCompilati->removeElement($modulo);
      $modulo->removePratica($this);
    }

    return $this;
  }

  /**
   * @return Collection
   */
  public function getModuliCompilati(): Collection
  {
    $files = [];
    /** @var Allegato $item */
    foreach ($this->moduliCompilati as $item) {
      $files[$item->getCreatedAt()->format('U')] = $item;
    }
    krsort($files, SORT_NUMERIC);

    return new ArrayCollection($files);
  }

  /**
   * @param ModuloCompilato $modulo
   * @return $this
   */
  public function addModuloCompilato(ModuloCompilato $modulo)
  {
    if (!$this->moduliCompilati->contains($modulo)) {
      $this->moduliCompilati->add($modulo);
      $modulo->addPratica($this);
    }

    return $this;
  }

  /**
   * @param AllegatoOperatore $modulo
   * @return $this
   */
  public function removeAllegatoOperatore(AllegatoOperatore $allegato)
  {
    if ($this->allegatiOperatore->contains($allegato)) {
      $this->allegatiOperatore->removeElement($allegato);
      $allegato->removePratica($this);
    }

    return $this;
  }

  /**
   * @return Collection
   */
  public function getAllegatiOperatore(): Collection
  {
    return $this->allegatiOperatore;
  }

  /**
   * @param AllegatoOperatore $allegato
   * @return $this
   */
  public function addAllegatoOperatore(AllegatoOperatore $allegato)
  {
    if (!$this->allegatiOperatore->contains($allegato)) {
      $this->allegatiOperatore->add($allegato);
      $allegato->addPratica($this);
    }

    return $this;
  }

  /**
   * @return Collection
   */
  public function getMessages()
  {
    return $this->messages;
  }

  /**
   * @return Collection
   */
  public function getPublicMessages()
  {
    $publicMessages = new ArrayCollection();
    foreach ($this->getMessages() as $message) {
      /** @var Message $message */
      if ($message->getVisibility() == Message::VISIBILITY_APPLICANT) {
        $publicMessages[] = $message;
      }
    }

    return $publicMessages;
  }


  /**
   * @return string
   */
  public function getOggetto()
  {
    return $this->oggetto;
  }

  /**
   * @param string $oggetto
   * @return $this
   */
  public function setOggetto(string $oggetto)
  {
    $this->oggetto = $oggetto;

    return $this;
  }

  public function setGeneratedSubject()
  {

    if (!empty($this->getOggetto())) {
      return $this;
    }

    if (!$this->user) {
      return $this;
    }

    $subject = $this->generateSubject();
    $this->setOggetto($subject);
    return $this;
  }

  /**
   * @return string
   */
  public function generateSubject(): string
  {
    return StringUtils::shortenString($this->getServizio()->getName())
    . ' ' . $this->getUser()->getNome()
    . ' ' . $this->getUser()->getCognome()
    . ' ' . $this->getUser()->getCodiceFiscale() ?? $this->getUser()->getId();
  }

  /**
   * @return string
   */
  public function getNumeroProtocollo()
  {
    return $this->numeroProtocollo;
  }

  /**
   * @param string $numeroProtocollo
   *
   * @return $this
   */
  public function setNumeroProtocollo($numeroProtocollo)
  {
    $this->numeroProtocollo = $numeroProtocollo;

    return $this;
  }

  /**
   * @ORM\PreFlush()
   */
  public function arrayToJson()
  {
    $this->numeriProtocollo = json_encode($this->getNumeriProtocollo()->toArray());
  }

  /**
   * @return mixed
   */
  public function getNumeriProtocollo()
  {
    if (!$this->numeriProtocollo instanceof ArrayCollection) {
      $this->jsonToArray();
    }

    return $this->numeriProtocollo;
  }

  /**
   * @ORM\PostLoad()
   * @ORM\PostUpdate()
   */
  public function jsonToArray()
  {
    if ($this->numeriProtocollo) {
      $this->numeriProtocollo = new ArrayCollection(json_decode($this->numeriProtocollo));
    } else {
      $this->numeriProtocollo = new ArrayCollection();
    }
  }

  /**
   * @ORM\PreFlush()
   */
  public function serializeStatuses()
  {
    if ($this->storicoStati instanceof Collection) {
      $storicoStati = $this->storicoStati->toArray();
      // Con il passaggio alla 4.4 alcuni uuid vengono serializzati non come stringa ma come byte, questo crea un problema di salvataggio su charset utf8 del database
      // Il codice seguente bonifica il salvataggio forzando la serializzazioen della stringa
      // Todo: rivedere completamente in futuro lo storico degli stati
      foreach ($storicoStati as $timestampKey => $timestampValue) {
        foreach ($timestampValue as $key => $value) {
          foreach ($value as $k => $v) {
            if (isset($v['message_id']) && !empty($v['message_id']) && $v['message_id'] instanceof UuidInterface) {
              $storicoStati[$timestampKey][$key][$k]['message_id'] = $v['message_id']->toString();
            }
          }
        }
      }
      $this->storicoStati = serialize($storicoStati);
    }
  }

  /**
   * @return int
   */
  public function getLatestStatusChangeTimestamp(): int
  {
    return $this->latestStatusChangeTimestamp;
  }

  /**
   * @param int $latestStatusChangeTimestamp
   * @return Pratica
   */
  public function setLatestStatusChangeTimestamp($latestStatusChangeTimestamp)
  {
    $this->latestStatusChangeTimestamp = $latestStatusChangeTimestamp;

    return $this;
  }

  /**
   * @return int
   */
  public function getLatestCPSCommunicationTimestamp(): int
  {
    return $this->latestCPSCommunicationTimestamp;
  }

  /**
   * @param int $latestCPSCommunicationTimestamp
   * @return Pratica
   */
  public function setLatestCPSCommunicationTimestamp($latestCPSCommunicationTimestamp)
  {
    $this->latestCPSCommunicationTimestamp = $latestCPSCommunicationTimestamp;

    return $this;
  }

  /**
   * @return int
   */
  public function getLatestOperatoreCommunicationTimestamp(): int
  {
    return $this->latestOperatoreCommunicationTimestamp;
  }

  /**
   * @param int $latestOperatoreCommunicationTimestamp
   * @return Pratica
   */
  public function setLatestOperatoreCommunicationTimestamp($latestOperatoreCommunicationTimestamp)
  {
    $this->latestOperatoreCommunicationTimestamp = $latestOperatoreCommunicationTimestamp;

    return $this;
  }

  /**
   * @return mixed
   */
  public function getSubmissionTime()
  {
    return $this->submissionTime;
  }

  /**
   * @param $submissionTime
   *
   * @return $this
   */
  public function setSubmissionTime($submissionTime)
  {
    $this->submissionTime = $submissionTime;

    return $this;
  }

  /**
   * @return Collection
   */
  public function getStoricoStati(): Collection
  {
    if (!$this->storicoStati instanceof Collection) {
      $this->storicoStati = new ArrayCollection(unserialize($this->storicoStati));
    }

    return $this->storicoStati;
  }

  /**
   * @param int $status
   * @return null|int
   */
  public function getLatestTimestampForStatus($status)
  {
    $latestTimestamp = null;
    $array = $this->storicoStati->toArray();
    ksort($array);
    foreach ($array as $timestamp => $stati) {
      foreach ($stati as $stato) {
        if ($stato[0] == $status) {
          $latestTimestamp = $timestamp;
        }
      }
    }

    return $latestTimestamp;
  }

  /**
   * @return string
   */
  public function getRichiedenteNome()
  {
    return $this->richiedenteNome;
  }

  /**
   * @param string $richiedenteNome
   *
   * @return Pratica
   */
  public function setRichiedenteNome($richiedenteNome)
  {
    $this->richiedenteNome = $richiedenteNome;

    return $this;
  }

  /**
   * @return string
   */
  public function getRichiedenteCognome()
  {
    return $this->richiedenteCognome;
  }

  /**
   * @param string $richiedenteCognome
   *
   * @return Pratica
   */
  public function setRichiedenteCognome($richiedenteCognome)
  {
    $this->richiedenteCognome = $richiedenteCognome;

    return $this;
  }

  /**
   * @return string
   */
  public function getRichiedenteCodiceFiscale()
  {
    return $this->richiedenteCodiceFiscale;
  }

  /**
   * @param string $richiedenteCodiceFiscale
   *
   * @return Pratica
   */
  public function setRichiedenteCodiceFiscale(string $richiedenteCodiceFiscale)
  {
    $this->richiedenteCodiceFiscale = $richiedenteCodiceFiscale;

    return $this;
  }

  /**
   * @return string
   */
  public function getRichiedenteLuogoNascita()
  {
    return $this->richiedenteLuogoNascita;
  }

  /**
   * @param string $richiedenteLuogoNascita
   *
   * @return Pratica
   */
  public function setRichiedenteLuogoNascita($richiedenteLuogoNascita)
  {
    $this->richiedenteLuogoNascita = $richiedenteLuogoNascita;

    return $this;
  }

  /**
   * @return string
   */
  public function getRichiedenteDataNascita()
  {
    return $this->richiedenteDataNascita;
  }

  /**
   * @param string $richiedenteDataNascita
   *
   * @return Pratica
   */
  public function setRichiedenteDataNascita($richiedenteDataNascita)
  {
    $this->richiedenteDataNascita = $richiedenteDataNascita;

    return $this;
  }

  /**
   * @return string
   */
  public function getRichiedenteIndirizzoResidenza()
  {
    return $this->richiedenteIndirizzoResidenza;
  }

  /**
   * @param string $richiedenteIndirizzoResidenza
   *
   * @return Pratica
   */
  public function setRichiedenteIndirizzoResidenza($richiedenteIndirizzoResidenza)
  {
    $this->richiedenteIndirizzoResidenza = $richiedenteIndirizzoResidenza;

    return $this;
  }

  /**
   * @return string
   */
  public function getRichiedenteCapResidenza()
  {
    return $this->richiedenteCapResidenza;
  }

  /**
   * @param string $richiedenteCapResidenza
   *
   * @return Pratica
   */
  public function setRichiedenteCapResidenza($richiedenteCapResidenza)
  {
    $this->richiedenteCapResidenza = $richiedenteCapResidenza;

    return $this;
  }

  /**
   * @return string
   */
  public function getRichiedenteCittaResidenza()
  {
    return $this->richiedenteCittaResidenza;
  }

  /**
   * @param string $richiedenteCittaResidenza
   *
   * @return Pratica
   */
  public function setRichiedenteCittaResidenza($richiedenteCittaResidenza)
  {
    $this->richiedenteCittaResidenza = $richiedenteCittaResidenza;

    return $this;
  }

  /**
   * @return string
   */
  public function getRichiedenteTelefono()
  {
    return $this->richiedenteTelefono;
  }

  /**
   * @param string $richiedenteTelefono
   *
   * @return Pratica
   */
  public function setRichiedenteTelefono($richiedenteTelefono)
  {
    $this->richiedenteTelefono = $richiedenteTelefono;

    return $this;
  }

  /**
   * @return string
   */
  public function getRichiedenteEmail()
  {
    return $this->richiedenteEmail;
  }

  /**
   * @param string $richiedenteEmail
   *
   * @return Pratica
   */
  public function setRichiedenteEmail($richiedenteEmail)
  {
    $this->richiedenteEmail = $richiedenteEmail;

    return $this;
  }

  /**
   * @return boolean
   */
  public function isAccettoIstruzioni()
  {
    return $this->accettoIstruzioni;
  }

  /**
   * @param boolean $accettoIstruzioni
   *
   * @return Pratica
   */
  public function setAccettoIstruzioni($accettoIstruzioni)
  {
    $this->accettoIstruzioni = $accettoIstruzioni;

    return $this;
  }

  /**
   * @return int
   */
  public function getLastCompiledStep(): int
  {
    return $this->lastCompiledStep;
  }

  /**
   * @param int $lastCompiledStep
   *
   * @return $this
   */
  public function setLastCompiledStep($lastCompiledStep)
  {
    $this->lastCompiledStep = $lastCompiledStep;

    return $this;
  }

  /**
   * @return string
   */
  public function getInstanceId()
  {
    return $this->instanceId;
  }

  /**
   * @param $instanceId
   *
   * @return $this
   */
  public function setInstanceId($instanceId)
  {
    $this->instanceId = $instanceId;

    return $this;
  }

  /**
   * @return bool|null
   */
  public function getEsito()
  {
    return $this->esito;
  }

  /**
   * @param bool $esito
   */
  public function setEsito(?bool $esito)
  {
    $this->esito = $esito;
  }

  /**
   * @return string|null
   */
  public function getMotivazioneEsito()
  {
    return $this->motivazioneEsito;
  }

  /**
   * @param string $motivazioneEsito
   */
  public function setMotivazioneEsito(?string $motivazioneEsito)
  {
    $this->motivazioneEsito = $motivazioneEsito;
  }


  /**
   * @return RispostaOperatore
   */
  public function getRispostaOperatore()
  {
    return $this->rispostaOperatore;
  }

  /**
   * @param RispostaOperatore $rispostaOperatore
   * @return $this
   */
  public function addRispostaOperatore($rispostaOperatore)
  {
    $this->rispostaOperatore = $rispostaOperatore;

    return $this;
  }

  /**
   * @return $this
   */
  public function removeRispostaOperatore()
  {
    $this->rispostaOperatore = null;

    return $this;
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
   * @return $this
   */
  public function setEnte($ente)
  {
    $this->ente = $ente;

    return $this;
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

  /**
   * @return Collection
   */
  public function getRichiesteIntegrazione()
  {
    return $this->richiesteIntegrazione;
  }

  /**
   * @param ArrayCollection $richiesteIntegrazione
   *
   * @return $this
   */
  public function setRichiesteIntegrazione($richiesteIntegrazione)
  {
    $this->richiesteIntegrazione = $richiesteIntegrazione;

    return $this;
  }

  public function haUnaRichiestaDiIntegrazioneAttiva()
  {
    return $this->getRichiestaDiIntegrazioneAttiva() != false;
  }

  /**
   * @return RichiestaIntegrazione|null
   */
  public function getRichiestaDiIntegrazioneAttiva()
  {
    foreach ($this->getRichiesteIntegrazione() as $richiestaIntegrazione) {
      if ($richiestaIntegrazione->getStatus() == RichiestaIntegrazione::STATUS_PENDING) {
        return $richiestaIntegrazione;
      }
    }

    return null;
  }

  public function addRichiestaIntegrazione(RichiestaIntegrazione $integration)
  {
    if (!$this->richiesteIntegrazione->contains($integration)) {
      $this->richiesteIntegrazione->add($integration);
      //$integration->addPratica($this);
    }

    return $this;
  }

  /**
   * @return mixed
   */
  public function getDelegaType()
  {
    return $this->delegaType;
  }

  /**
   * @param mixed $delegaType
   */
  public function setDelegaType($delegaType)
  {
    $this->delegaType = $delegaType;
  }

  /**
   * @return array
   */
  public function getDelegaData()
  {
    return $this->delegaData;
  }

  /**
   * @param $delegaData
   */
  public function setDelegaData($delegaData)
  {
    $this->delegaData = $delegaData;
  }

  /**
   * @return array
   */
  public function getDelegaDataArray()
  {
    if (is_array($this->getDelegaData())) {
      return $this->getDelegaData();
    }

    return \json_decode($this->getDelegaData(), true);
  }

  public function getTipiDelega()
  {
    if ($this->tipiDelega === null) {
      $this->tipiDelega = array();
      $class = new \ReflectionClass(__CLASS__);
      $constants = $class->getConstants();
      foreach ($constants as $name => $value) {
        if (strpos($name, 'TIPO_DELEGA_') !== false) {
          $this->tipiDelega[] = $value;
        }
      }
    }

    return $this->tipiDelega;
  }

  public function getRelatedCFs()
  {
    return $this->relatedCFs;
  }

  /**
   * @param array $relatedCFs
   * @return $this
   */
  public function setRelatedCFs($relatedCFs)
  {
    $this->relatedCFs = $relatedCFs;

    return $this;
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
   *
   * @return Pratica
   */
  public function setPaymentType($paymentType)
  {
    $this->paymentType = $paymentType;

    return $this;
  }

  /**
   * @return array
   */
  public function getPaymentData()
  {
    return $this->paymentData ?? [];
  }

  /**
   * @param array $paymentData
   */
  public function setPaymentData($paymentData)
  {
    $this->paymentData = $paymentData;
  }

  /**
   * @return array
   */
  public function getPaymentDataArray()
  {
    if (is_string($this->getPaymentData())) {
      return \json_decode($this->getPaymentData());
    } elseif (is_array($this->getPaymentData())) {
      return $this->getPaymentData();
    }

    return [];
  }

  /**
   * @return string
   */
  public function getHash()
  {
    return $this->hash;
  }

  /**
   * @param string $hash
   * @return $this
   */
  public function setHash(string $hash)
  {
    $this->hash = $hash;

    return $this;
  }

  public function isValidHash($hash, $hashValidity)
  {
    if ($hash && $hash == $this->getHash()) {
      $timestamp = explode('-', $hash);
      $timestamp = end($timestamp);
      $maxVisibilityDate = (new \DateTime())->setTimestamp($timestamp)->modify('+ '.$hashValidity.' days');

      return $maxVisibilityDate >= new \DateTime('now');
    }

    return false;
  }

  /**
   * @return mixed
   */
  public function getParent()
  {
    return $this->parent;
  }

  /**
   * @param Pratica $parent
   * @return Pratica
   */
  public function setParent($parent)
  {
    $this->parent = $parent;

    return $this;
  }

  /**
   * @return Pratica
   */
  public function getRootParent()
  {
    if (!$this->getParent()) {
      return $this;
    } else {
      $hasParent = true;
      $parent = $this;
      while ($hasParent) {
        if ($parent->getParent()) {
          $parent = $parent->getParent();
        } else {
          $hasParent = false;
        }
      }

      return $parent;
    }
  }

  public function getTreeIdList()
  {
    $list = new \ArrayObject();
    $root = $this->getRootParent();
    $list[] = $root->getId();
    foreach ($root->getChildren() as $child) {
      $this->appendSubTreeIdList($child, $list);
    }

    return $list->getArrayCopy();
  }

  private function appendSubTreeIdList(Pratica $root, $list)
  {
    $list[] = $root->getId();
    foreach ($root->getChildren() as $child) {
      $this->appendSubTreeIdList($child, $list);
    }
  }

  /**
   * @return ArrayCollection
   */
  public function getChildren()
  {
    return $this->children;
  }

  /**
   * @return ServiceGroup
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
    $this->serviceGroup = $serviceGroup;
  }

  /**
   * @return mixed
   */
  public function getFolderId()
  {
    return $this->folderId;
  }

  /**
   * @param mixed $folderId
   */
  public function setFolderId($folderId)
  {
    $this->folderId = $folderId;
  }

  /**
   * @return Allegato
   */
  public function getWithdrawAttachment()
  {
    $attachments = $this->allegati;
    /** @var Allegato $item */
    foreach ($attachments as $item) {
      if ($item->getType() == Ritiro::TYPE_DEFAULT) {
        return $item;
      }
    }

    return null;
  }

  /**
   * @return bool
   */
  public function isInFinalStates()
  {
    return in_array($this->status, self::FINAL_STATES);
  }

  public function getAllowedStates()
  {
    $states = self::ALLOWED_MANUAL_CHANGE_STATES;

    // Escludo lo stato in attesa di pagamento se la pratica non prevede pagamento
    if ($this->getServizio()->getPaymentRequired() == Servizio::PAYMENT_NOT_REQUIRED) {
      if (($key = array_search(self::STATUS_PAYMENT_PENDING, $states)) !== false) {
        unset($states[$key]);
      }
    }

    // Escludo lo stato di presa in carico se la pratica prevede il flusso inoltro
    if ($this->getServizio()->getWorkflow() == Servizio::WORKFLOW_FORWARD) {
      if (($key = array_search(self::STATUS_PENDING, $states)) !== false) {
        unset($states[$key]);
      }
    }

    // Escludo lo stato attuale della pratica
    if (($key = array_search($this->getStatus(), $states)) !== false) {
      unset($states[$key]);
    }

    return $states;
  }

  /**
   * @return bool
   */
  public function canBeAssigned()
  {
    return ($this->status < self::STATUS_PROCESSING && $this->servizio->getWorkflow() != Servizio::WORKFLOW_FORWARD);

  }

  public function getIntegrationAnswers()
  {
    $answers = new ArrayCollection();
    foreach ($this->allegati as $a) {
      if ($a instanceof RispostaIntegrazione) {
        $answers->add($a);
      }
    }

    return $answers;
  }

  public function getHistory()
  {
    $history = [];
    foreach ($this->getStoricoStati() as $k => $v) {
      foreach ($v as $change) {
        $transition = new Transition();
        $transition->setStatusCode($change[0]);
        $transition->setStatusName(strtolower($this->getStatusNameByCode($change[0])));
        if (isset($change[1]['message']) && !empty($change[1]['message'])) {
          $transition->setMessage($change[1]['message']);
        }
        if (isset($change[1]['message_id']) && !empty($change[1]['message_id'])) {
          $transition->setMessageId($change[1]['message_id']);
        }
        try {
          $date = new \DateTime();
          $date->setTimestamp($k);
          $transition->setDate($date);
        } catch (\Exception $e) {
        }
        $transition->setDate($date);
        $history[] = $transition;
      }
    }

    return $history;
  }

  /**
   * @return Collection
   */
  public function getMeetings()
  {
    return $this->meetings;
  }

  /**
   * @param ArrayCollection $meetings
   *
   * @return $this
   */
  public function setMeetings(ArrayCollection $meetings)
  {
    $this->meetings = $meetings;

    return $this;
  }

  public function addMeeting(Meeting $meeting)
  {
    if (!$this->meetings->contains($meeting)) {
      $this->meetings->add($meeting);
      $meeting->addApplication($this);
    }

    return $this;
  }

  /**
   * @return mixed
   */
  public function getBackofficeFormData()
  {
    return $this->backofficeFormData;
  }

  /**
   * @param mixed $backofficeFormData
   */
  public function setBackofficeFormData($backofficeFormData): void
  {
    $this->backofficeFormData = $backofficeFormData;
  }

  /**
   * @return string
   */
  public function getLocale(): ?string
  {
    return $this->locale ?? 'it';
  }

  /**
   * @param string $locale
   */
  public function setLocale(string $locale): void
  {
    $this->locale = $locale;
  }

  public function needsPayment(): bool
  {
    if ($this->getStatus() == Pratica::STATUS_PAYMENT_PENDING) {
      return true;
    }

    return false;
  }

  public function getFlowChangedAt(): ?\DateTime
  {
    switch ($this->getStatus()) {
      case Pratica::STATUS_DRAFT:
        return $this->createdAt;

      case Pratica::STATUS_PRE_SUBMIT:
      case Pratica::STATUS_SUBMITTED:
        $date = new \DateTime();
        try {
          $date->setTimestamp($this->getSubmissionTime());
        } catch (\Exception $e) {
        }

        return $date;

      case Pratica::STATUS_REGISTERED:
        $date = new \DateTime();
        try {
          $date->setTimestamp($this->getProtocolTime());

        } catch (\Exception $e) {
        }

        return $date;
      default:
        $date = new \DateTime();
        try {
          $date->setTimestamp($this->getLatestStatusChangeTimestamp());
        } catch (\Exception $e) {
        }

        return $date;
    }
  }

  public function getRemainingResponseTime(): int
  {
    $now = new \DateTime();
    if($this->servizio->getStatus() === Servizio::STATUS_SCHEDULED){
      // get days passed from the scheduled final date
      $daysFromSubmission = $now->diff($this->servizio->getScheduledTo())->days;
    } else {
      // get days passed from the time of submission
      $submissionDate = (new \DateTime())->setTimestamp($this->submissionTime);
      $daysFromSubmission = $now->diff($submissionDate)->days;
    }
    return $this->servizio->getMaxResponseTime() - $daysFromSubmission;
  }

  /**
   * @return bool
   */
  public function isFormIOType(): bool
  {
    if (in_array($this->getType(), [self::TYPE_FORMIO, self::TYPE_BUILTIN])) {
      return true;
    } else {
      return false;
    }
  }
}
