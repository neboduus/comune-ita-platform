<?php

namespace AppBundle\Entity;

use AppBundle\Dto\UserAuthenticationData;
use AppBundle\Model\Transition;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="AppBundle\Entity\PraticaRepository")
 * @ORM\Table(name="pratica")
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="type", type="string")
 * @ORM\DiscriminatorMap({"default" = "Pratica",
 *     "iscrizione_asilo_nido" = "IscrizioneAsiloNido",
 *     "autolettura_acqua" = "AutoletturaAcqua",
 *     "contributo_pannolini" = "ContributoPannolini",
 *     "cambio_residenza" = "CambioResidenza",
 *     "allacciamento_acquedotto" = "AllacciamentoAcquedotto",
 *     "certificato_nascita" = "CertificatoNascita",
 *     "attestazione_anagrafica" = "AttestazioneAnagrafica",
 *     "liste_elettorali" = "ListeElettorali",
 *     "stato_famiglia" = "StatoFamiglia",
 *     "occupazione_suolo_pubblico" = "OccupazioneSuoloPubblico",
 *     "contributo_associazioni" = "ContributoAssociazioni",
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
 *     "atto_nascita" = "AttoNascita",
 *     "certificato_morte" = "CertificatoMorte",
 *     "estratto_morte" = "EstrattoMorte",
 *     "atto_morte" = "AttoMorte",
 *     "certificato_matrimonio" = "CertificatoMatrimonio",
 *     "estratto_matrimonio" = "EstrattoMatrimonio",
 *     "atto_matrimonio" = "AttoMatrimonio",
 *     "nulla_osta_matrimonio" = "NullaOstaMatrimonio",
 *     "form_io" = "FormIO"
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

  const FINAL_STATES = [
    Pratica::STATUS_COMPLETE,
    Pratica::STATUS_CANCELLED,
    Pratica::STATUS_WITHDRAW
  ];

  const ACCEPTED = true;
  const REJECTED = false;

  const TYPE_DEFAULT = "default";
  const TYPE_ISCRIZIONE_ASILO_NIDO = "iscrizione_asilo_nido";
  const TYPE_AUTOLETTURA_ACQUA = "autolettura_acqua";
  const TYPE_CONTRIBUTO_PANNOLINI = "contributo_pannolini";
  const TYPE_CAMBIO_RESIDENZA = "cambio_residenza";
  const TYPE_ALLACCIAMENTO_AQUEDOTTO = "allacciamento_aquedotto";
  const TYPE_CERTIFICATO_NASCITA = "certificato_nascita";
  const TYPE_ATTESTAZIONE_ANAGRAFICA = "attestazione_anagrafica";
  const TYPE_LISTE_ELETTORALI = "liste_elettorali";
  const TYPE_STATO_FAMIGLIA = "stato_famiglia";

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
  const TYPE_CONTRIBUTO_ASSOCIAZIONI = "contributo_associazioni";
  const TYPE_ISCRIZIONE_REGISTRO_ASSOCIAZIONI = "iscrizione_registro_associazioni";

  const TYPE_ESTRATTO_NASCITA = "estratto_nascita";
  const TYPE_ATTO_NASCITA = "atto_nascita";
  const TYPE_CERTIFICATO_MORTE = "certificato_morte";
  const TYPE_ESTRATTO_MORTE = "estratto_morte";
  const TYPE_ATTO_MORTE = "atto_morte";
  const TYPE_CERTIFICATO_MATRIMONIO = "certificato_matrimonio";
  const TYPE_ESTRATTO_MATRIMONIO = "estratto_matrimonio";
  const TYPE_ATTO_MATRIMONIO = "atto_matrimonio";
  const TYPE_NULLA_OSTA_MATRIMONIO = "nulla_osta_matrimonio";

  const TYPE_FORMIO = 'form_io';

  const TIPO_DELEGA_DELEGATO = 'delegato';
  const TIPO_DELEGA_INCARICATO = 'incaricato';
  const TIPO_DELEGA_ALTRO = 'altro';

  const HASH_SESSION_KEY = 'anonymous-hash';

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
   * @ORM\ManyToOne(targetEntity="AppBundle\Entity\User")
   * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false)
   */
  private $user;

  /**
   * @ORM\Column(type="json", nullable=true)
   */
  private $authenticationData;

  /**
   * @ORM\ManyToOne(targetEntity="AppBundle\Entity\UserSession")
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
   * @ORM\ManyToOne(targetEntity="AppBundle\Entity\OperatoreUser")
   * @ORM\JoinColumn(name="operatore_id", referencedColumnName="id", nullable=true)
   */
  private $operatore;

  /**
   * @ORM\Column(type="text", nullable=true)
   * @var string
   */
  private $oggetto;

  /**
   * @ORM\ManyToMany(targetEntity="AppBundle\Entity\Allegato", inversedBy="pratiche", orphanRemoval=false)
   * @var ArrayCollection
   * @Assert\Valid(traverse=true)
   */
  private $allegati;

  /**
   * @ORM\ManyToMany(targetEntity="AppBundle\Entity\ModuloCompilato", inversedBy="pratiche2", orphanRemoval=false)
   * @var ArrayCollection
   * @Assert\Valid(traverse=true)
   */
  private $moduliCompilati;

  /**
   * @ORM\ManyToMany(targetEntity="AppBundle\Entity\AllegatoOperatore", inversedBy="pratiche3", orphanRemoval=false)
   * @var ArrayCollection
   * @Assert\Valid(traverse=true)
   */
  private $allegatiOperatore;

  /**
   * @ORM\OneToMany(targetEntity="AppBundle\Entity\Message", mappedBy="application")
   * @ORM\OrderBy({"createdAt" = "ASC"})
   * @var ArrayCollection
   */
  private $messages;


  /**
   * @ORM\OneToOne(targetEntity="AppBundle\Entity\RispostaOperatore", orphanRemoval=false)
   * @ORM\JoinColumn(nullable=true)
   * @var RispostaOperatore
   */
  private $rispostaOperatore;

  /**
   * @ORM\OneToMany(targetEntity="AppBundle\Entity\ComponenteNucleoFamiliare", mappedBy="pratica", cascade={"persist"}, orphanRemoval=true)
   * @ORM\JoinColumn(nullable=true)
   * @var ArrayCollection
   */
  private $nucleoFamiliare;

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
   * @var string
   * @ORM\Column(type="text", nullable=true)
   */
  private $data;

  /**
   * @var string
   * @ORM\Column(type="text", nullable=true)
   */
  private $commenti;

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
   * @var string
   * @ORM\Column(type="string", nullable=true)
   */
  private $iban;

  /**
   * @var string
   * @ORM\Column(type="string", nullable=true)
   */
  private $intestatarioConto;

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
   * @ORM\OneToMany(targetEntity="AppBundle\Entity\RichiestaIntegrazione", mappedBy="praticaPerCuiServeIntegrazione", orphanRemoval=false)
   * @var ArrayCollection
   */
  private $richiesteIntegrazione;

  /**
   * @ORM\Column(type="string", nullable=true)
   */
  private $delegaType;

  /**
   * @ORM\Column(type="json_array", nullable=true)
   * @var $paymentData array
   */
  private $delegaData;

  /**
   * @ORM\Column(type="json_array", options={"jsonb":true}, nullable=true)
   * @var $relatedCFs array
   */
  private $relatedCFs;

  /**
   * @ORM\ManyToOne(targetEntity="PaymentGateway")
   * @ORM\JoinColumn(name="payment_type", referencedColumnName="id", nullable=true)
   */
  private $paymentType;

  /**
   * @ORM\Column(type="json_array", nullable=true)
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
   * @ORM\ManyToOne(targetEntity="AppBundle\Entity\ServiceGroup", inversedBy="applications")
   * @ORM\JoinColumn(name="service_group_id", referencedColumnName="id", nullable=true)
   */
  private $serviceGroup;

  /**
   * @ORM\Column(type="guid", nullable=true)
   */
  private $folderId;

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
    $this->numeriProtocollo = new ArrayCollection();
    $this->allegati = new ArrayCollection();
    $this->moduliCompilati = new ArrayCollection();
    $this->allegatiOperatore = new ArrayCollection();
    $this->messages = new ArrayCollection();
    $this->nucleoFamiliare = new ArrayCollection();
    $this->latestStatusChangeTimestamp = $this->latestCPSCommunicationTimestamp = $this->latestOperatoreCommunicationTimestamp = -10000000;
    $this->storicoStati = new ArrayCollection();
    $this->lastCompiledStep = 0;
    $this->richiesteIntegrazione = new ArrayCollection();
    $this->children = new ArrayCollection();
  }

  public function __clone()
  {
    $this->id = Uuid::uuid4();
    $this->numeroProtocollo = null;
    $this->numeroFascicolo = null;
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
    $data = (array)json_decode($this->authenticationData, true);

    return UserAuthenticationData::fromArray($data);
  }

  /**
   * @param UserAuthenticationData $authenticationData
   *
   * @return $this
   */
  public function setAuthenticationData(UserAuthenticationData $authenticationData)
  {
    $this->authenticationData = json_encode($authenticationData);

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

  public function getStatusNameByCode($code)
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
   * @param OperatoreUser $operatore
   *
   * @return Pratica
   */
  public function setOperatore(OperatoreUser $operatore)
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
   * @return mixed
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
   * @return Collection
   */
  public function getAllegati()
  {
    return $this->allegati;
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
    return $this->moduliCompilati;
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
    $this->numeriProtocollo = new ArrayCollection(json_decode($this->numeriProtocollo));
  }

  /**
   * @return Collection
   */
  public function getNucleoFamiliare()
  {
    return $this->nucleoFamiliare;
  }

  /**
   * @param Collection $nucleoFamiliare
   *
   * @return $this
   */
  public function setNucleoFamiliare(Collection $nucleoFamiliare)
  {
    $this->nucleoFamiliare = $nucleoFamiliare;

    return $this;
  }

  /**
   * @param ComponenteNucleoFamiliare $componente
   *
   * @return $this
   */
  public function addComponenteNucleoFamiliare(ComponenteNucleoFamiliare $componente)
  {
    if (!$this->nucleoFamiliare->contains($componente)) {
      $componente->setPratica($this);
      $this->nucleoFamiliare->add($componente);
    }

    return $this;
  }

  /**
   * @param ComponenteNucleoFamiliare $componente
   *
   * @return $this
   */
  public function removeComponenteNucleoFamiliare(ComponenteNucleoFamiliare $componente)
  {
    if ($this->nucleoFamiliare->contains($componente)) {
      $this->nucleoFamiliare->removeElement($componente);
      $componente->setPratica(null);
    }

    return $this;
  }

  /**
   * @return ArrayCollection
   */
  public function getCommenti()
  {
    if (!$this->commenti instanceof ArrayCollection) {
      $this->parseCommenti();
    }

    return $this->commenti;
  }

  /**
   * @param string $commenti
   *
   * @return Pratica
   */
  public function setCommenti($commenti)
  {
    $this->commenti = $commenti;

    return $this;
  }

  /**
   * @param array $commento
   *
   * @return Pratica
   */
  public function addCommento(array $commento)
  {
    if (!$this->getCommenti()->exists(function ($key, $value) use ($commento) {
      return $value['text'] == $commento['text'];
    })
    ) {
      $this->getCommenti()->add($commento);
    }

    return $this;
  }


  /**
   * @ORM\PreFlush()
   */
  public function convertCommentiToString()
  {
    $data = [];
    foreach ($this->getCommenti() as $commento) {
      $data[] = serialize($commento);
    }
    $this->commenti = implode('##', $data);
  }

  /**
   * @ORM\PreFlush()
   */
  public function serializeStatuses()
  {
    if ($this->storicoStati instanceof Collection) {
      $this->storicoStati = serialize($this->storicoStati->toArray());
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
   * @param $commentoSerialized
   */
  private function parseCommentStringIntoArrayCollection($commentoSerialized)
  {
    $commento = unserialize($commentoSerialized);
    if (is_array($commento) && isset($commento['text']) && !empty($commento['text'])) {
      if (!$this->commenti->exists(function ($key, $value) use ($commento) {
        return $value['text'] == $commento['text'];
      })
      ) {
        $this->commenti->add($commento);
      }
    }
  }

  /**
   * @ORM\PostLoad()
   * @ORM\PostUpdate()
   */
  private function parseCommenti()
  {
    $data = [];
    if ($this->commenti !== null) {
      $data = explode('##', $this->commenti);
    }
    $this->commenti = new ArrayCollection();
    foreach ($data as $commentoSeriliazed) {
      $this->parseCommentStringIntoArrayCollection($commentoSeriliazed);
    }
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
   * @return string
   */
  public function getIban()
  {
    return $this->iban;
  }

  /**
   * @param $iban
   *
   * @return $this
   */
  public function setIban($iban)
  {
    $this->iban = $iban;
    return $this;
  }

  /**
   * @return string
   */
  public function getIntestatarioConto()
  {
    return $this->intestatarioConto;
  }

  /**
   * @param string $intestatarioConto
   */
  public function setIntestatarioConto($intestatarioConto)
  {
    $this->intestatarioConto = $intestatarioConto;
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
  public function setUserCompilationNotes(string $userCompilationNotes)
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
      $integration->addPratica($this);
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
   * @return PaymentGateway
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
    return $this->paymentData;
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
    return \json_decode($this->getPaymentData());
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
      $maxVisibilityDate = (new \DateTime())->setTimestamp($timestamp)->modify('+ ' . $hashValidity . ' days');

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
    if (!$this->getParent()){
      return $this;
    }else{
      $hasParent = true;
      $parent = $this;
      while ($hasParent){
        if ($parent->getParent()){
          $parent = $parent->getParent();
        }else{
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
    foreach ($root->getChildren() as $child){
      $this->appendSubTreeIdList($child, $list);
    }

    return $list->getArrayCopy();
  }

  private function appendSubTreeIdList(Pratica $root, $list)
  {
    $list[] = $root->getId();
    foreach ($root->getChildren() as $child){
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
   * @return mixed
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
        $transition->setStatusName($this->getStatusNameByCode($change[0]));
        if (isset($change[1]['message']) && !empty($change[1]['message'])) {
          $transition->setMessage($change[1]['message']);
        }
        try {
          $date = new \DateTime();
          $date->setTimestamp($k);
          $transition->setDate($date);
        } catch (\Exception $e) {}
        $transition->setDate($date);
        $history[]= $transition;
      }
    }
    return $history;
  }
}
