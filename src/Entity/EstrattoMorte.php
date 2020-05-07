<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class EstrattoMorte
 * @ORM\Entity
 */
class EstrattoMorte extends Pratica implements DematerializedFormPratica
{
    const TIPOLOGIA_CERTIFICATO_ANAGRAFICO_SEMPLICE = 'semplice';
    const TIPOLOGIA_CERTIFICATO_ANAGRAFICO_INTERNAZIONALE = 'internazionale';

    /**
     * @ORM\Column(type="json_array", options={"jsonb":true})
     * @var $dematerializedForms array
     */
    private $dematerializedForms;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=true)
     */
    private $tipologiaCertificatoAnagrafico;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=true)
     */
    private $statoEsteroCertificatoAnagrafico;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=true)
     */
    private $usoCertificatoAnagrafico;

    /**
     * @var boolean
     * @ORM\Column(name="allegato_operatore_richiesto", type="boolean")
     */
    private $allegatoOperatoreRichiesto;

    private $tipologieCertificatoAnagrafico;

    public function __construct()
    {
        parent::__construct();
        $this->type = self::TYPE_ESTRATTO_MORTE;
        $this->allegatoOperatoreRichiesto = true;
    }

    /**
     * @return array
     */
    public function getDematerializedForms()
    {
        return $this->dematerializedForms;
    }

    /**
     * @param [] $dematerializedForms
     * @return $this
     */
    public function setDematerializedForms($dematerializedForms)
    {
        $this->dematerializedForms = $dematerializedForms;

        return $this;
    }

    /**
     * @return string
     */
    public function getTipologiaCertificatoAnagrafico()
    {
        return $this->tipologiaCertificatoAnagrafico;
    }

    /**
     * @param string $tipologiaCertificatoAnagrafico
     */
    public function setTipologiaCertificatoAnagrafico(string $tipologiaCertificatoAnagrafico)
    {
        $this->tipologiaCertificatoAnagrafico = $tipologiaCertificatoAnagrafico;
    }

    /**
     * @return string
     */
    public function getStatoEsteroCertificatoAnagrafico()
    {
        return $this->statoEsteroCertificatoAnagrafico;
    }

    /**
     * @param string $statoEsteroCertificatoAnagrafico
     */
    public function setStatoEsteroCertificatoAnagrafico(string $statoEsteroCertificatoAnagrafico)
    {
        $this->statoEsteroCertificatoAnagrafico = $statoEsteroCertificatoAnagrafico;
    }

    /**
     * @return string
     */
    public function getUsoCertificatoAnagrafico()
    {
        return $this->usoCertificatoAnagrafico;
    }

    /**
     * @param string $usoCertificatoAnagrafico
     */
    public function setUsoCertificatoAnagrafico(string $usoCertificatoAnagrafico)
    {
        $this->usoCertificatoAnagrafico = $usoCertificatoAnagrafico;
    }

    /**
     * @return array|null
     * @throws \ReflectionException
     */
    public function getTipologieCertificatoAnagrafico()
    {
        if ($this->tipologieCertificatoAnagrafico === null) {
            $this->tipologieCertificatoAnagrafico = array();
            $class = new \ReflectionClass(__CLASS__);
            $constants = $class->getConstants();
            foreach ($constants as $name => $value) {
                if (strpos($name, 'TIPOLOGIA_CERTIFICATO_ANAGRAFICO_') !== false) {
                    $this->tipologieCertificatoAnagrafico[] = $value;
                }
            }
        }
        return $this->tipologieCertificatoAnagrafico;
    }

    /**
     * @return boolean
     */
    public function isAllegatoOperatoreRichiesto(): bool
    {
        return $this->allegatoOperatoreRichiesto;
    }
}
