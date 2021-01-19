<?php
namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;


/**
 * Class AttoNascita
 * @ORM\Entity
 */
class AttoNascita extends Pratica implements DematerializedFormPratica
{

    /**
     * @ORM\Column(type="json_array", options={"jsonb":true})
     * @var $dematerializedForms array
     */
    private $dematerializedForms;

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
        $this->type = self::TYPE_ATTO_NASCITA;
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
     * @return boolean
     */
    public function isAllegatoOperatoreRichiesto(): bool
    {
        return $this->allegatoOperatoreRichiesto;
    }

}