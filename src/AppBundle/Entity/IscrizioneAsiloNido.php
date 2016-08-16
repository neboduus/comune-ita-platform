<?php

namespace AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class IscrizioneAsiloNido
 *
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class IscrizioneAsiloNido extends Pratica
{
    /**
     * @var bool
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $accetto_istruzioni;

    /**
     * @var bool
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $accetto_utilizzo;

    /**
     * @var AsiloNido
     *
     * @ORM\ManyToOne(targetEntity="AsiloNido")
     * @ORM\JoinColumn(name="asilo_id", referencedColumnName="id")
     */
    private $struttura;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=true)
     */
    private $richiedente_nome;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=true)
     */
    private $richiedente_cognome;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=true)
     */
    private $richiedente_luogo_nascita;

    /**
     * @var \DateTime
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $richiedente_data_nascita;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=true)
     */
    private $richiedente_indirizzo_residenza;

    /**
     * @var string
     * @ORM\Column(type="integer", nullable=true)
     */
    private $richiedente_cap_residenza;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=true)
     */
    private $richiedente_citta_residenza;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=true)
     */
    private $richiedente_telefono;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=true)
     */
    private $richiedente_email;

    /**
     * @var ArrayCollection
     * @ORM\Column(type="array", nullable=true)
     */
    private $nucleo_familiare;


    public function __construct()
    {
        parent::__construct();
        $this->type = self::TYPE_ISCRIZIONE_ASILO_NIDO;
    }

    /**
     * @return mixed
     */
    public function getNucleoFamiliare()
    {
        if (!$this->nucleo_familiare instanceof ArrayCollection) {
            $this->stringToArray();
        }

        return $this->nucleo_familiare;
    }

    /**
     * @param ComponenteNucleoFamiliare $componente
     *
     * @return $this
     */
    public function addComponenteNucleoFamiliare(ComponenteNucleoFamiliare $componente)
    {
        if (!$this->nucleo_familiare->contains($componente)) {
            $this->nucleo_familiare->add($componente);
        }

        return $this;
    }

    /**
     * @param $nucleoFamiliare
     *
     * @return $this
     */
    public function setNucleoFamiliare($nucleoFamiliare)
    {
        $this->nucleo_familiare = $nucleoFamiliare;

        return $this;
    }

    /**
     * @return AsiloNido
     */
    public function getStruttura()
    {
        return $this->struttura;
    }

    /**
     * @param AsiloNido $struttura
     *
     * @return IscrizioneAsiloNido
     */
    public function setStruttura($struttura)
    {
        $this->struttura = $struttura;

        return $this;
    }

    public function jsonSerialize()
    {
        return $this;
    }

    /**
     * @return boolean
     */
    public function isAccettoIstruzioni()
    {
        return $this->accetto_istruzioni;
    }

    /**
     * @param boolean $accetto_istruzioni
     *
     * @return IscrizioneAsiloNido
     */
    public function setAccettoIstruzioni($accetto_istruzioni)
    {
        $this->accetto_istruzioni = $accetto_istruzioni;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isAccettoUtilizzo()
    {
        return $this->accetto_utilizzo;
    }

    /**
     * @param boolean $accetto_utilizzo
     *
     * @return IscrizioneAsiloNido
     */
    public function setAccettoUtilizzo($accetto_utilizzo)
    {
        $this->accetto_utilizzo = $accetto_utilizzo;

        return $this;
    }

    /**
     * @return string
     */
    public function getRichiedenteNome()
    {
        return $this->richiedente_nome;
    }

    /**
     * @param string $richiedente_nome
     *
     * @return IscrizioneAsiloNido
     */
    public function setRichiedenteNome($richiedente_nome)
    {
        $this->richiedente_nome = $richiedente_nome;

        return $this;
    }

    /**
     * @return string
     */
    public function getRichiedenteCognome()
    {
        return $this->richiedente_cognome;
    }

    /**
     * @param string $richiedente_cognome
     *
     * @return IscrizioneAsiloNido
     */
    public function setRichiedenteCognome($richiedente_cognome)
    {
        $this->richiedente_cognome = $richiedente_cognome;

        return $this;
    }

    /**
     * @return string
     */
    public function getRichiedenteLuogoNascita()
    {
        return $this->richiedente_luogo_nascita;
    }

    /**
     * @param string $richiedente_luogo_nascita
     *
     * @return IscrizioneAsiloNido
     */
    public function setRichiedenteLuogoNascita($richiedente_luogo_nascita)
    {
        $this->richiedente_luogo_nascita = $richiedente_luogo_nascita;

        return $this;
    }

    /**
     * @return string
     */
    public function getRichiedenteDataNascita()
    {
        return $this->richiedente_data_nascita;
    }

    /**
     * @param string $richiedente_data_nascita
     *
     * @return IscrizioneAsiloNido
     */
    public function setRichiedenteDataNascita($richiedente_data_nascita)
    {
        $this->richiedente_data_nascita = $richiedente_data_nascita;

        return $this;
    }

    /**
     * @return string
     */
    public function getRichiedenteIndirizzoResidenza()
    {
        return $this->richiedente_indirizzo_residenza;
    }

    /**
     * @param string $richiedente_indirizzo_residenza
     *
     * @return IscrizioneAsiloNido
     */
    public function setRichiedenteIndirizzoResidenza($richiedente_indirizzo_residenza)
    {
        $this->richiedente_indirizzo_residenza = $richiedente_indirizzo_residenza;

        return $this;
    }

    /**
     * @return string
     */
    public function getRichiedenteCapResidenza()
    {
        return $this->richiedente_cap_residenza;
    }

    /**
     * @param string $richiedente_cap_residenza
     *
     * @return IscrizioneAsiloNido
     */
    public function setRichiedenteCapResidenza($richiedente_cap_residenza)
    {
        $this->richiedente_cap_residenza = $richiedente_cap_residenza;

        return $this;
    }

    /**
     * @return string
     */
    public function getRichiedenteCittaResidenza()
    {
        return $this->richiedente_citta_residenza;
    }

    /**
     * @param string $richiedente_citta_residenza
     *
     * @return IscrizioneAsiloNido
     */
    public function setRichiedenteCittaResidenza($richiedente_citta_residenza)
    {
        $this->richiedente_citta_residenza = $richiedente_citta_residenza;

        return $this;
    }

    /**
     * @return string
     */
    public function getRichiedenteTelefono()
    {
        return $this->richiedente_telefono;
    }

    /**
     * @param string $richiedente_telefono
     *
     * @return IscrizioneAsiloNido
     */
    public function setRichiedenteTelefono($richiedente_telefono)
    {
        $this->richiedente_telefono = $richiedente_telefono;

        return $this;
    }

    /**
     * @return string
     */
    public function getRichiedenteEmail()
    {
        return $this->richiedente_email;
    }

    /**
     * @param string $richiedente_email
     *
     * @return IscrizioneAsiloNido
     */
    public function setRichiedenteEmail($richiedente_email)
    {
        $this->richiedente_email = $richiedente_email;

        return $this;
    }

    /**
     * @ORM\PreFlush()
     */
    public function arrayToString()
    {
        $data = (array)$this->getNucleoFamiliare()->toArray();
        foreach ($data as $element) {
            $data[] = $element;
        }
        $this->nucleo_familiare = serialize($data);
    }

    /**
     * @ORM\PostLoad()
     * @ORM\PostUpdate()
     */
    public function stringToArray()
    {
        $collection = new ArrayCollection();
        if ($this->nucleo_familiare !== null) {
            $data = unserialize($this->nucleo_familiare);
            foreach ($data as $element) {
                $collection->add($element);
            }
        }
        $this->nucleo_familiare = $collection;
    }

}
