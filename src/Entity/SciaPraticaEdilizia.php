<?php
namespace App\Entity;

use App\Mapper\Giscom\SciaPraticaEdilizia as MappedPraticaEdilizia;
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
     * TODO: create entities, mapper, service definition for each of these services
     - Comunicazione OPERE LIBERE (01-Col) - 1_Comunicazione OPERE LIBERE_rev24ott2018.pdf
     - CILA - Comunicazione di inizio lavori asseverata (02_Cila) - 2_CILA_rev24ott2018.pdf
     - SCIA – Segnalazione certificata d’inizio attività (03_Scia) - 3_SCIA+Elenco_rev 12novRE.pdf
     - Domanda di permesso di costruire (04-PdC) - 4_Domanda di permesso di costruire rev_24ott.pdf
     - Domanda di permesso di costruire in sanatoria – (05-PdCs) - 5_Domanda di permcostr_in
    sanatoria_rev 24ott.pdf
     - CIL - Comunicazione di inizio lavori – (7-Cil) - 7_Comunicazione INIZIO LAVORI_rev_15novRE.pdf
     - Dichiarazione di Ultimazione Lavori – (8-Dul) - 8_Dichiarazione Ultimazione Lavori_rev_24ott.pdf
     - Domanda di autorizzazione paesaggistica al sindaco – (12-Aps) - 12_Autorizzazione Paesaggio
    SINDACO_rev 24ott.pdf
     */

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
        $this->type = self::TYPE_SCIA_PRATICA_EDILIZIA;
        $this->dematerializedForms = (new \App\Mapper\Giscom\SciaPraticaEdilizia())->toHash();
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
