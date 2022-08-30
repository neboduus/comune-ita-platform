<?php
namespace App\Entity;

use App\Mapper\Giscom\SciaPraticaEdilizia;
use App\Entity\SciaPraticaEdilizia as Base;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class SciaPraticaEdilizia
 * @ORM\Entity
 */
class AutorizzazionePaesaggisticaSindaco extends Base
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
        $this->type = Pratica::TYPE_AUTORIZZAZIONE_PAESAGGISTICA_SINDACO;
        $this->dematerializedForms = (new SciaPraticaEdilizia(['tipo'=> Pratica::TYPE_AUTORIZZAZIONE_PAESAGGISTICA_SINDACO]))->toHash();
    }

    public function getAllegatiIdList()
    {
        $giscomPratica = new SciaPraticaEdilizia($this->getDematerializedForms());
        return $giscomPratica->getAllegatiIdArray();
    }

}
