<?php

namespace AppBundle\Mapper\Giscom\SciaPraticaEdilizia;

use AppBundle\Entity\Pratica;

class ElencoAllegatiAllaDomanda extends AbstractSciaPraticaEdiliziaMappable
{
    const TYPE = 'scia_ediliza_allegati_modulo_scia';

    private $mappings = [
        Pratica::TYPE_COMUNICAZIONE_OPERE_LIBERE => [
            'DOM_CONDOMINIO'
        ],

        Pratica::TYPE_COMUNICAZIONE_INIZIO_LAVORI_ASSEVERATA => [],

        Pratica::TYPE_SCIA_PRATICA_EDILIZIA => [
            'DOM_ISPAT',
            'DOM_EDIFICI-STORICI',
            'DOM_DOC_PAGAMENTO_CONTRIBUTO'
        ],

        Pratica::TYPE_DOMANDA_DI_PERMESSO_DI_COSTRUIRE => [
            'DOM_ISPAT',
            'DOM_EDIFICI-STORICI',
            'DOM_OBBLIGHI_SICUREZZA',
            'DOM_DOC_PAGAMENTO_CONTRIBUTO',
            'DOM_PROGETTO_OPERE'
        ],

        Pratica::TYPE_DOMANDA_DI_PERMESSO_DI_COSTRUIRE_IN_SANATORIA => [
            'DOM_EDIFICI-STORICI',
            'DOM_DOC_PAGAMENTO_CONTRIBUTO'
        ],

        Pratica::TYPE_COMUNICAZIONE_INIZIO_LAVORI => [
            'DOM_OBBLIGHI_SICUREZZA',
            'DOM_DOC_PAGAMENTO_CONTRIBUTO'
        ],
        Pratica::TYPE_DICHIARAZIONE_ULTIMAZIONE_LAVORI => [],

        Pratica::TYPE_AUTORIZZAZIONE_PAESAGGISTICA_SINDACO => [],

        Pratica::TYPE_SEGNALAZIONE_CERTIFICATA_AGIBILITA => [
            'DOM_DOC_PAGAMENTO_CONTRIBUTO'
        ]
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
