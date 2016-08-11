<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class CPSUser
 * @ORM\Entity
 *
 * @package AppBundle\Entity
 */
class CPSUser extends User
{
    /**
     * @var boolean
     *
     * @ORM\Column(name="terms_accepted", type="boolean")
     */
    private $termsAccepted = false;

    /**
     * @var string
     *
     * @ORM\Column(name="codice_fiscale", type="string", unique=true)
     */
    private $codiceFiscale;

    /**
     * @var string
     *
     * @ORM\Column(name="cap_domicilio", type="string", nullable=true)
     */
    private $capDomicilio;

    /**
     * @var string
     *
     * @ORM\Column(name="cap_residenza", type="string", nullable=true)
     */
    private $capResidenza;

    /**
     * @var string
     *
     * @ORM\Column(name="cellulare", type="string", nullable=true)
     */
    private $cellulare;

    /**
     * @var string
     *
     * @ORM\Column(name="citta_domicilio", type="string", nullable=true)
     */
    private $cittaDomicilio;

    /**
     * @var string
     *
     * @ORM\Column(name="citta_residenza", type="string", nullable=true)
     */
    private $cittaResidenza;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="data_nascita", type="datetime", nullable=true)
     */
    private $dataNascita;

    /**
     * @var string
     *
     * @ORM\Column(name="email_alt", type="string", nullable=true)
     */
    private $emailAlt;

    /**
     * @var string
     *
     * @ORM\Column(name="indirizzo_domicilio", type="string", nullable=true)
     */
    private $indirizzoDomicilio;

    /**
     * @var string
     *
     * @ORM\Column(name="indirizzo_residenza", type="string", nullable=true)
     */
    private $indirizzoResidenza;

    /**
     * @var string
     *
     * @ORM\Column(name="luogo_nascita", type="string", nullable=true)
     */
    private $luogoNascita;

    /**
     * @var string
     *
     * @ORM\Column(name="provincia_domicilio", type="string", nullable=true)
     */
    private $provinciaDomicilio;

    /**
     * @var string
     *
     * @ORM\Column(name="provincia_nascita", type="string", nullable=true)
     */
    private $provinciaNascita;

    /**
     * @var string
     *
     * @ORM\Column(name="provincia_residenza", type="string", nullable=true)
     */
    private $provinciaResidenza;

    /**
     * @var string
     *
     * @ORM\Column(name="sesso", type="string", nullable=true)
     */
    private $sesso;

    /**
     * @var string
     *
     * @ORM\Column(name="stato_domicilio", type="string", nullable=true)
     */
    private $statoDomicilio;

    /**
     * @var string
     *
     * @ORM\Column(name="stato_nascita", type="string", nullable=true)
     */
    private $statoNascita;

    /**
     * @var string
     *
     * @ORM\Column(name="stato_residenza", type="string", nullable=true)
     */
    private $statoResidenza;

    /**
     * @var string
     *
     * @ORM\Column(name="telefono", type="string", nullable=true)
     */
    private $telefono;

    /**
     * @var string
     *
     * @ORM\Column(name="titolo", type="string", nullable=true)
     */
    private $titolo;

    /**
     * @var string
     *
     * @ORM\Column(name="x509certificate_issuerdn", type="string", nullable=true)
     */
    private $x509certificate_issuerdn;

    /**
     * @var string
     *
     * @ORM\Column(name="x509certificate_subjectdn", type="string", nullable=true)
     */
    private $x509certificate_subjectdn;

    /**
     * @var string
     *
     * @ORM\Column(name="x509certificate_base64", type="string", nullable=true)
     */
    private $x509certificate_base64;

    /**
     * CPSUser constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->type = self::USER_TYPE_CPS;
    }

    /**
     * @return boolean
     */
    public function getTermsAccepted()
    {
        return $this->termsAccepted;
    }

    /**
     * @param $termsAccepted
     *
     * @return User
     */
    public function setTermsAccepted($termsAccepted)
    {
        $this->termsAccepted = $termsAccepted;

        return $this;
    }

    /**
 * @return string
 */
    public function getCodiceFiscale()
    {
        return $this->codiceFiscale;
    }

    /**
     * @param $codiceFiscale
     *
     * @return $this
     */
    public function setCodiceFiscale($codiceFiscale)
    {
        $this->codiceFiscale = $codiceFiscale;

        return $this;
    }

    /**
     * @return string
     */
    public function getCapDomicilio()
    {
        return $this->capDomicilio;
    }

    /**
     * @param $capDomicilio
     *
     * @return $this
     */
    public function setCapDomicilio($capDomicilio)
    {
        $this->capDomicilio = $capDomicilio;

        return $this;
    }

    /**
     * @return string
     */
    public function getCapResidenza()
    {
        return $this->capResidenza;
    }

    /**
     * @param $capResidenza
     *
     * @return $this
     */
    public function setCapResidenza($capResidenza)
    {
        $this->capResidenza = $capResidenza;

        return $this;
    }

    /**
     * @return string
     */
    public function getCellulare()
    {
        return $this->cellulare;
    }

    /**
     * @param string $cellulare
     *
     * @return $this
     */
    public function setCellulare($cellulare)
    {
        $this->cellulare = $cellulare;

        return $this;
    }

    /**
     * @return string
     */
    public function getCittaDomicilio()
    {
        return $this->cittaDomicilio;
    }

    /**
     * @param string $cittaDomicilio
     *
     * @return $this
     */
    public function setCittaDomicilio($cittaDomicilio)
    {
        $this->cittaDomicilio = $cittaDomicilio;

        return $this;
    }

    /**
     * @return string
     */
    public function getCittaResidenza()
    {
        return $this->cittaResidenza;
    }

    /**
     * @param string $cittaResidenza
     *
     * @return $this
     */
    public function setCittaResidenza($cittaResidenza)
    {
        $this->cittaResidenza = $cittaResidenza;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getDataNascita()
    {
        return $this->dataNascita;
    }

    /**
     * @param \DateTime $dataNascita
     *
     * @return $this
     */
    public function setDataNascita(\DateTime $dataNascita)
    {
        $this->dataNascita = $dataNascita;

        return $this;
    }

    /**
     * @return string
     */
    public function getEmailAlt()
    {
        return $this->emailAlt;
    }

    /**
     * @param string $emailAddress
     *
     * @return $this
     */
    public function setEmailAlt($emailAddress)
    {
        $this->emailAlt = $emailAddress;

        return $this;
    }

    /**
     * @return string
     */
    public function getIndirizzoDomicilio()
    {
        return $this->indirizzoDomicilio;
    }

    /**
     * @param string $indirizzoDomicilio
     *
     * @return $this
     */
    public function setIndirizzoDomicilio($indirizzoDomicilio)
    {
        $this->indirizzoDomicilio = $indirizzoDomicilio;

        return $this;
    }

    /**
     * @return string
     */
    public function getIndirizzoResidenza()
    {
        return $this->indirizzoResidenza;
    }

    /**
     * @param string $indirizzoResidenza
     *
     * @return $this
     */
    public function setIndirizzoResidenza($indirizzoResidenza)
    {
        $this->indirizzoResidenza = $indirizzoResidenza;

        return $this;
    }

    /**
     * @return string
     */
    public function getLuogoNascita()
    {
        return $this->luogoNascita;
    }

    /**
     * @param string $luogoNascita
     *
     * @return $this
     */
    public function setLuogoNascita($luogoNascita)
    {
        $this->luogoNascita = $luogoNascita;

        return $this;
    }

    /**
     * @return string
     */
    public function getProvinciaDomicilio()
    {
        return $this->provinciaDomicilio;
    }

    /**
     * @param string $provinciaDomicilio
     *
     * @return $this
     */
    public function setProvinciaDomicilio($provinciaDomicilio)
    {
        $this->provinciaDomicilio = $provinciaDomicilio;

        return $this;
    }

    /**
     * @return string
     */
    public function getProvinciaNascita()
    {
        return $this->provinciaNascita;
    }

    /**
     * @param string $provinciaNascita
     *
     * @return $this
     */
    public function setProvinciaNascita($provinciaNascita)
    {
        $this->provinciaNascita = $provinciaNascita;

        return $this;
    }

    /**
     * @return string
     */
    public function getProvinciaResidenza()
    {
        return $this->provinciaResidenza;
    }

    /**
     * @param string $provinciaResidenza
     *
     * @return $this
     */
    public function setProvinciaResidenza($provinciaResidenza)
    {
        $this->provinciaResidenza = $provinciaResidenza;

        return $this;
    }

    /**
     * @return string
     */
    public function getSesso()
    {
        return $this->sesso;
    }

    /**
     * @param string $sesso
     *
     * @return $this
     */
    public function setSesso($sesso)
    {
        $this->sesso = $sesso;

        return $this;
    }

    /**
     * @return string
     */
    public function getStatoDomicilio()
    {
        return $this->statoDomicilio;
    }

    /**
     * @param string $statoDomicilio
     *
     * @return $this
     */
    public function setStatoDomicilio($statoDomicilio)
    {
        $this->statoDomicilio = $statoDomicilio;

        return $this;
    }

    /**
     * @return string
     */
    public function getStatoNascita()
    {
        return $this->statoNascita;
    }

    /**
     * @param string $statoNascita
     *
     * @return $this
     */
    public function setStatoNascita($statoNascita)
    {
        $this->statoNascita = $statoNascita;

        return $this;
    }

    /**
     * @return string
     */
    public function getStatoResidenza()
    {
        return $this->statoResidenza;
    }

    /**
     * @param string $statoResidenza
     *
     * @return $this
     */
    public function setStatoResidenza($statoResidenza)
    {
        $this->statoResidenza = $statoResidenza;

        return $this;
    }

    /**
     * @return string
     */
    public function getTelefono()
    {
        return $this->telefono;
    }

    /**
     * @param string $telefono
     *
     * @return $this
     */
    public function setTelefono($telefono)
    {
        $this->telefono = $telefono;

        return $this;
    }

    /**
     * @return string
     */
    public function getTitolo()
    {
        return $this->titolo;
    }

    /**
     * @param string $titolo
     *
     * @return $this;
     */
    public function setTitolo($titolo)
    {
        $this->titolo = $titolo;

        return $this;
    }

    /**
     * @return string
     */
    public function getX509certificateIssuerdn()
    {
        return $this->x509certificate_issuerdn;
    }

    /**
     * @param string $x509certificate_issuerdn
     *
     * @return $this
     */
    public function setX509certificateIssuerdn($x509certificate_issuerdn)
    {
        $this->x509certificate_issuerdn = $x509certificate_issuerdn;

        return $this;
    }

    /**
     * @return string
     */
    public function getX509certificateSubjectdn()
    {
        return $this->x509certificate_subjectdn;
    }

    /**
     * @param string $x509certificate_subjectdn
     *
     * @return $this
     */
    public function setX509certificateSubjectdn($x509certificate_subjectdn)
    {
        $this->x509certificate_subjectdn = $x509certificate_subjectdn;

        return $this;
    }

    /**
     * @return string
     */
    public function getX509certificateBase64()
    {
        return $this->x509certificate_base64;
    }

    /**
     * @param string $x509certificate_base64
     *
     * @return $this;
     */
    public function setX509certificateBase64($x509certificate_base64)
    {
        $this->x509certificate_base64 = $x509certificate_base64;

        return $this;
    }

}
