<?php
namespace App\Entity;

use App\Mapper\Giscom\SciaPraticaEdilizia;
use App\Entity\SciaPraticaEdilizia as Base;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class SciaPraticaEdilizia
 * @ORM\Entity
 */
class ComunicazioneOpereLibere extends Base
{

    /**
     * @ORM\Column(type="json", options={"jsonb":true})
     * @var array
     */
    protected $dematerializedForms;

    /**
     * SciaPraticaEdilizia constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->type = Pratica::TYPE_COMUNICAZIONE_OPERE_LIBERE;
        $this->dematerializedForms = (new SciaPraticaEdilizia(['tipo'=> Pratica::TYPE_COMUNICAZIONE_OPERE_LIBERE]))->toHash();
    }

    public function getAllegatiIdList()
    {
        $giscomPratica = new SciaPraticaEdilizia($this->getDematerializedForms());
        return $giscomPratica->getAllegatiIdArray();
    }

}
