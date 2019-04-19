<?php

namespace AppBundle\Mapper\Giscom\SciaPraticaEdilizia;

use AppBundle\Entity\Pratica;

class ElencoAllegatiAllaDomanda extends AbstractSciaPraticaEdiliziaMappable
{
    const TYPE = 'scia_ediliza_allegati_modulo_scia';

    private $mappings = [
        Pratica::TYPE_COMUNICAZIONE_OPERE_LIBERE => ['DOM_CONDOMINIO'],
        Pratica::TYPE_SCIA_PRATICA_EDILIZIA => ['DOM_ISPAT','DOM_EDIFICI-STORICI'],
        Pratica::TYPE_COMUNICAZIONE_INIZIO_LAVORI_ASSEVERATA => [],
        Pratica::TYPE_DOMANDA_DI_PERMESSO_DI_COSTRUIRE => ['DOM_ISPAT','DOM_EDIFICI-STORICI', 'DOM_URBANIZZAZIONE', 'DOM_CONTRIBUTO'],
        Pratica::TYPE_DOMANDA_DI_PERMESSO_DI_COSTRUIRE_IN_SANATORIA => ['DOM_EDIFICI-STORICI', 'DOM_CONTRIBUTO', 'DOM_SICUREZZA'],
        Pratica::TYPE_COMUNICAZIONE_INIZIO_LAVORI => ['DOM_CONTRIBUTO','DOM_CONTRIBUTO'],
        Pratica::TYPE_DICHIARAZIONE_ULTIMAZIONE_LAVORI => [],
        Pratica::TYPE_AUTORIZZAZIONE_PAESAGGISTICA_SINDACO => [],
//        'segnalazione_certificata_agibilita' => [],
    ];

    private $commonFiles = [
        'DOM_DELEGA',
        'DOM_CI',
        'DOM_CF',
        'DOM_PRIVACY',
    ];

    private $commonMandatoryFiles = [
        'DOM_CI',
        'DOM_CF',
    ];

    public function getProperties()
    {
        return array_merge($this->commonFiles, $this->mappings[$this->tipo] ?? []);
    }

    public function getRequiredFields($tipoIntervento)
    {
        return $this->commonMandatoryFiles;
    }
}
