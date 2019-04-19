<?php
namespace AppBundle\Entity;

use AppBundle\Mapper\Giscom\SciaPraticaEdilizia;
use AppBundle\Entity\SciaPraticaEdilizia as Base;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class SciaPraticaEdilizia
 * @ORM\Entity
 */
class ComunicazioneInizioLavoriAsseverata extends Base
{

    /**
     * @ORM\Column(type="json_array", options={"jsonb":true})
     * @var $dematerializedForms array
     */
    protected $dematerializedForms;

    /**
     * SciaPraticaEdilizia constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->type = Pratica::TYPE_COMUNICAZIONE_INIZIO_LAVORI_ASSEVERATA;
        $this->dematerializedForms = (new SciaPraticaEdilizia(['tipo'=> Pratica::TYPE_COMUNICAZIONE_INIZIO_LAVORI_ASSEVERATA]))->toHash();
    }

    public function getAllegatiIdList()
    {
        $giscomPratica = new SciaPraticaEdilizia($this->getDematerializedForms());
        return $giscomPratica->getAllegatiIdArray();
    }

}