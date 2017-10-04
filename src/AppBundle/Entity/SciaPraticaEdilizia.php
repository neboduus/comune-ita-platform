<?php
namespace AppBundle\Entity;

use AppBundle\Mapper\Giscom\SciaPraticaEdilizia as MappedPraticaEdilizia;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class SciaPraticaEdilizia
 * @ORM\Entity
 */
class SciaPraticaEdilizia extends Pratica implements DematerializedFormPratica, DematerializedFormAllegatiContainer, GiscomPratica
{

    /**
     * @ORM\Column(type="json_array", options={"jsonb":true})
     * @var $dematerializedForms array
     */
    private $dematerializedForms;

    /**
     * SciaPraticaEdilizia constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->type = self::TYPE_SCIA_PRATICA_EDILIZIA;
        $this->dematerializedForms = (new \AppBundle\Mapper\Giscom\SciaPraticaEdilizia())->toHash();
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

    public function getAllegatiIdList()
    {
        $giscomPratica = new MappedPraticaEdilizia($this->getDematerializedForms());
        return $giscomPratica->getAllegatiIdArray();
    }

}