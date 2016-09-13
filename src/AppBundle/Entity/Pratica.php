<?php

namespace AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity
 * @ORM\Table(name="pratica")
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="type", type="string")
 * @ORM\DiscriminatorMap({"default" = "Pratica", "iscrizione_asilo_nido" = "IscrizioneAsiloNido"})
 * @ORM\HasLifecycleCallbacks
 */
class Pratica
{
    const STATUS_CANCELLED = 0;
    const STATUS_DRAFT = 1;
    const STATUS_SUBMITTED = 2;
    const STATUS_COMPLETE = 3;
    const STATUS_REGISTERED = 4;
    const STATUS_PENDING = 5;

    const TYPE_DEFAULT = "default";
    const TYPE_ISCRIZIONE_ASILO_NIDO = "iscrizione_asilo_nido";

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
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\CPSUser")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false)
     */
    private $user;

    /**
     * @ORM\ManyToOne(targetEntity="Servizio")
     * @ORM\JoinColumn(name="servizio_id", referencedColumnName="id", nullable=false)
     */
    private $servizio;

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
     * @ORM\ManyToMany(targetEntity="AppBundle\Entity\Allegato", inversedBy="pratiche", orphanRemoval=false)
     * @var ArrayCollection
     * @Assert\Valid(traverse=true)
     */
    private $allegati;

    /**
     * @ORM\OneToMany(targetEntity="ComponenteNucleoFamiliare", mappedBy="pratica", cascade={"persist"}, orphanRemoval=true)
     * @var ArrayCollection $nucleo_familiare
     */
    private $nucleoFamiliare;

    /**
     * @ORM\Column(type="integer", name="creation_time")
     */
    private $creationTime;

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
     * Pratica constructor.
     */
    public function __construct()
    {
        if (!$this->id) {
            $this->id = Uuid::uuid4();
        }
        $this->creationTime = time();
        $this->status = self::STATUS_DRAFT;
        $this->type = self::TYPE_DEFAULT;
        $this->numeroFascicolo = null;
        $this->numeriProtocollo = new ArrayCollection();
        $this->allegati = new ArrayCollection();
        $this->nucleoFamiliare = new ArrayCollection();
        $this->latestStatusChangeTimestamp = $this->latestCPSCommunicationTimestamp = $this->latestOperatoreCommunicationTimestamp = -10000000;
    }

    /**
     * @return CPSUser
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param CPSUser $user
     *
     * @return $this
     */
    public function setUser(CPSUser $user)
    {
        $this->user = $user;

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
        if ($this->statusName === null) {
            $class = new \ReflectionClass(__CLASS__);
            $constants = $class->getConstants();
            foreach ($constants as $name => $value) {
                if ($value == $this->status) {
                    $this->statusName = $name;
                    break;
                }
            }
        }

        return $this->statusName;
    }

    /**
     * @param integer $status
     *
     * @return $this
     */
    public function setStatus($status)
    {
        $this->status = $status;
        $this->latestStatusChangeTimestamp = time();

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
     * @param Ente $ente
     *
     * @return static
     */
    public function setEnte(Ente $ente)
    {
        $this->ente = $ente;

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
        return (string) $this->getId();
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
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
     * @param string $numeroDiProtocollo
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
     * @return mixed
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
        //TODO: testare e sentire con Nardelli come gestire i nueri di protocollo per gli allegati
        if ($this->allegati->contains($allegato)) {
            $this->allegati->removeElement($allegato);
            $allegato->removePratica($this);
        }

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
     * @param ArrayCollection $nucleoFamiliare
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
    public function addNucleoFamiliare(ComponenteNucleoFamiliare $componente)
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
    public function removeNucleoFamiliare(ComponenteNucleoFamiliare $componente)
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
     * @param $commentoSeriliazed
     */
    private function parseCommentStringIntoArrayCollection($commentoSeriliazed)
    {
        $commento = unserialize($commentoSeriliazed);
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

}
