<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class OccupazioneSuoloPubblico
 *
 * @ORM\Entity
 */
class IscrizioneRegistroAssociazioni extends Pratica
{

    const TIPOLOGIA_ATTIVITA_COMMERCIALE = 'commerciale';
    const TIPOLOGIA_ATTIVITA_NON_COMMERCIALE = 'non_commerciale';

    const TIPOLOGIA_CONTRIBUTO_BENI_STRUMENTALI = 'beni_strumentali';
    const TIPOLOGIA_CONTRIBUTO_FINI_ISTITUZIONALI_COMMERCIALI = 'fini_istituzionali_commerciali';
    const TIPOLOGIA_CONTRIBUTO_FINI_ISTITUZIONALI_NON_COMMERCIALI = 'fini_istituzionali_non_commerciali';
    const TIPOLOGIA_CONTRIBUTO_MANIFESTAZIONE_ISTITUZIONALE_NON_COMMERCIALE = 'manifestazione_istituzionale_non_commerciale';
    const TIPOLOGIA_CONTRIBUTO_COMMERCIALE = 'commerciale';


    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $ruoloUtenteOrgRichiedente;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $nome_associazione;
    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $natura_giuridica;
    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $sito;
    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $pagina_social;
    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $e_mail;
    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $numero_iscritti;
    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $modalita_di_adesione;
    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $attivita;
    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $obiettivi;
    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $sede_legale;
    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $sede_operativa;
    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $indirizzo;
    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $contatti;

    private $tipologieAttivita;

    private $tipologieUsoContributo;

    /**
     * OccupazioneSuoloPubblico constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->type = self::TYPE_ISCRIZIONE_REGISTRO_ASSOCIAZIONI;
    }

    public function getTipologieAttivita()
    {
        if ($this->tipologieAttivita === null) {
            $this->tipologieAttivita = array();
            $class = new \ReflectionClass(__CLASS__);
            $constants = $class->getConstants();
            foreach ($constants as $name => $value) {
                if (strpos($name, 'TIPOLOGIA_ATTIVITA_') !== false) {
                    $this->tipologieAttivita[] = $value;
                }
            }
        }
        return $this->tipologieAttivita;
    }

    public function getTipologieUsoContributo()
    {
        if ($this->tipologieUsoContributo === null) {
            $this->tipologieUsoContributo = array();
            $class = new \ReflectionClass(__CLASS__);
            $constants = $class->getConstants();
            foreach ($constants as $name => $value) {
                if (strpos($name, 'TIPOLOGIA_CONTRIBUTO_') !== false) {
                    $this->tipologieUsoContributo[] = $value;
                }
            }
        }
        return $this->tipologieUsoContributo;
    }

    /**
     * @return mixed
     */
    public function getNomeAssociazione()
    {
        return $this->nome_associazione;
    }

    /**
     * @param mixed $nome_associazione
     */
    public function setNomeAssociazione($nome_associazione): void
    {
        $this->nome_associazione = $nome_associazione;
    }

    /**
     * @return mixed
     */
    public function getNaturaGiuridica()
    {
        return $this->natura_giuridica;
    }

    /**
     * @param mixed $natura_giuridica
     */
    public function setNaturaGiuridica($natura_giuridica): void
    {
        $this->natura_giuridica = $natura_giuridica;
    }

    /**
     * @return mixed
     */
    public function getSito()
    {
        return $this->sito;
    }

    /**
     * @param mixed $sito
     */
    public function setSito($sito): void
    {
        $this->sito = $sito;
    }

    /**
     * @return mixed
     */
    public function getPaginaSocial()
    {
        return $this->pagina_social;
    }

    /**
     * @param mixed $pagina_social
     */
    public function setPaginaSocial($pagina_social): void
    {
        $this->pagina_social = $pagina_social;
    }

    /**
     * @return mixed
     */
    public function getEMail()
    {
        return $this->e_mail;
    }

    /**
     * @param mixed $e_mail
     */
    public function setEMail($e_mail): void
    {
        $this->e_mail = $e_mail;
    }

    /**
     * @return mixed
     */
    public function getNumeroIscritti()
    {
        return $this->numero_iscritti;
    }

    /**
     * @param mixed $numero_iscritti
     */
    public function setNumeroIscritti($numero_iscritti): void
    {
        $this->numero_iscritti = $numero_iscritti;
    }

    /**
     * @return mixed
     */
    public function getModalitaDiAdesione()
    {
        return $this->modalita_di_adesione;
    }

    /**
     * @param mixed $modalita_di_adesione
     */
    public function setModalitaDiAdesione($modalita_di_adesione): void
    {
        $this->modalita_di_adesione = $modalita_di_adesione;
    }

    /**
     * @return mixed
     */
    public function getAttivita()
    {
        return $this->attivita;
    }

    /**
     * @param mixed $attivita
     */
    public function setAttivita($attivita): void
    {
        $this->attivita = $attivita;
    }

    /**
     * @return mixed
     */
    public function getObiettivi()
    {
        return $this->obiettivi;
    }

    /**
     * @param mixed $obiettivi
     */
    public function setObiettivi($obiettivi): void
    {
        $this->obiettivi = $obiettivi;
    }

    /**
     * @return mixed
     */
    public function getSedeLegale()
    {
        return $this->sede_legale;
    }

    /**
     * @param mixed $sede_legale
     */
    public function setSedeLegale($sede_legale): void
    {
        $this->sede_legale = $sede_legale;
    }

    /**
     * @return mixed
     */
    public function getSedeOperativa()
    {
        return $this->sede_operativa;
    }

    /**
     * @param mixed $sede_operativa
     */
    public function setSedeOperativa($sede_operativa): void
    {
        $this->sede_operativa = $sede_operativa;
    }

    /**
     * @return mixed
     */
    public function getIndirizzo()
    {
        return $this->indirizzo;
    }

    /**
     * @param mixed $indirizzo
     */
    public function setIndirizzo($indirizzo): void
    {
        $this->indirizzo = $indirizzo;
    }

    /**
     * @return mixed
     */
    public function getContatti()
    {
        return $this->contatti;
    }

    /**
     * @param mixed $contatti
     */
    public function setContatti($contatti): void
    {
        $this->contatti = $contatti;
    }

    /**
     * @return mixed
     */
    public function getRuoloUtenteOrgRichiedente()
    {
        return $this->ruoloUtenteOrgRichiedente;
    }

    /**
     * @param mixed $ruoloUtenteOrgRichiedente
     */
    public function setRuoloUtenteOrgRichiedente($ruoloUtenteOrgRichiedente): void
    {
        $this->ruoloUtenteOrgRichiedente = $ruoloUtenteOrgRichiedente;
    }

}
