<?php
namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;


/**
 *
 * @ORM\Entity
 */
class CertificatoNascita extends Pratica
{
    const TIPOLOGIA_CERTIFICATO_ANAGRAFICO_SEMPLICE = 'semplice';
    const TIPOLOGIA_CERTIFICATO_ANAGRAFICO_GENITORI = 'genitori';

    /**
     * @var string
     * @ORM\Column(type="string", nullable=true)
     */
    private $tipologiaCertificatoAnagrafico;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=true)
     */
    private $usoCertificatoAnagrafico;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=true)
     */
    private $statoEsteroCertificatoAnagrafico;

    /**
     * @var boolean
     * @ORM\Column(name="allegato_operatore_richiesto", type="boolean")
     */
    private $allegatoOperatoreRichiesto;

    private $tipologieCertificatoAnagrafico;

    public function __construct()
    {
        parent::__construct();
        $this->type = self::TYPE_CERTIFICATO_NASCITA;
        $this->allegatoOperatoreRichiesto = true;
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
