<?php
namespace App\Entity;

use App\Mapper\Giscom\SciaPraticaEdilizia;
use App\Entity\SciaPraticaEdilizia as Base;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class SciaPraticaEdilizia
 * @ORM\Entity
 */
class DomandaDiPermessoDiCostruireInSanatoria extends Base
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
        $this->type = Pratica::TYPE_DOMANDA_DI_PERMESSO_DI_COSTRUIRE_IN_SANATORIA;
        $this->dematerializedForms = (new SciaPraticaEdilizia(['tipo'=> Pratica::TYPE_DOMANDA_DI_PERMESSO_DI_COSTRUIRE_IN_SANATORIA]))->toHash();
    }

    public function getAllegatiIdList()
    {
        $giscomPratica = new SciaPraticaEdilizia($this->getDematerializedForms());
        return $giscomPratica->getAllegatiIdArray();
    }

}
