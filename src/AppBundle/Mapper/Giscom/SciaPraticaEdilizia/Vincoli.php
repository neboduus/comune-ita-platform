<?php

namespace AppBundle\Mapper\Giscom\SciaPraticaEdilizia;

use AppBundle\Entity\Pratica;

class Vincoli extends AbstractSciaPraticaEdiliziaMappable
{
    const TYPE = 'scia_edilizia_ulteriori_allegati_tecnici';

    private $tipoMappedVincoli = [
        Pratica::TYPE_SCIA_PRATICA_EDILIZIA => [
            'VIN_TUTELA_PAESAGGISTICA',
            'VIN_BENI_CULTURALI',
            'VIN_IMPATTO_AMBIENTALE',
            'VIN_VALUTAZIONE_INCIDENZA',
            'VIN_AREE_AGRICOLE',
            'VIN_UTILIZZO_ACQUE_PUBBLICHE',
            'VIN_VINCOLO_IDROGEOLOGICO',
            'VIN_TUTELA_ACQUE_PUBBLICHE',
            'VIN_TUTELA_AMBIENTI_INQUINAMENTO',
            'VIN_TULP',
            'VIN_LIMITI_ELETTROMAGNETICI',
            'VIN_FASCE_RISPETTO_STRADALE',
            'VIN_FASCE_RISPETTO_FERROVIARIE',
            'VIN_FASCE_RISPETTO_AEROPORTUALE',
            'VIN_FASCE_RISPETTO_CIMITERIALE',
            'VIN_FASCE_RISPETTO_DEPURATORE',
            'VIN_FASCE_RISPETTO_INCIDENTE',
            'VIN_FASCE_RISPETTO_ALTRO',
            'VIN_IMPIANTI_TELECOMUNICAZIONI',
            'VIN_PREVENZIONE_INCENDI',
            'VIN_TULPS_ESERCIZI_PUBBLICI',
            'VIN_VISTO_CORRISPONDENZA',
            'VIN_IMPIANTI_ILLUMINAZIONE_ESTERNA',
            'VIN_ALTRO',
        ],
        Pratica::TYPE_DOMANDA_DI_PERMESSO_DI_COSTRUIRE => [
            'VIN_TUTELA_PAESAGGISTICA',
            'VIN_BENI_CULTURALI',
            'VIN_IMPATTO_AMBIENTALE',
            'VIN_VALUTAZIONE_INCIDENZA',
            'VIN_AREE_AGRICOLE',
            'VIN_UTILIZZO_ACQUE_PUBBLICHE',
            'VIN_VINCOLO_IDROGEOLOGICO',
            'VIN_TUTELA_ACQUE_PUBBLICHE',
            'VIN_TUTELA_AMBIENTI_INQUINAMENTO',
            'VIN_TULP',
            'VIN_LIMITI_ELETTROMAGNETICI',
            'VIN_FASCE_RISPETTO_STRADALE',
            'VIN_FASCE_RISPETTO_FERROVIARIE',
            'VIN_FASCE_RISPETTO_AEROPORTUALE',
            'VIN_FASCE_RISPETTO_CIMITERIALE',
            'VIN_FASCE_RISPETTO_DEPURATORE',
            'VIN_FASCE_RISPETTO_INCIDENTE',
            'VIN_FASCE_RISPETTO_ALTRO',
            'VIN_IMPIANTI_TELECOMUNICAZIONI',
            'VIN_PREVENZIONE_INCENDI',
            'VIN_TULPS_ESERCIZI_PUBBLICI',
            'VIN_VISTO_CORRISPONDENZA',
            'VIN_IMPIANTI_ILLUMINAZIONE_ESTERNA',
            'VIN_VERBALE_ASSEMBLEA',
            'VIN_ASSENSO_COMPROPRIETARI',
            'VIN_ALTRO',
        ],
        Pratica::TYPE_DOMANDA_DI_PERMESSO_DI_COSTRUIRE_IN_SANATORIA => [
            'VIN_IMPATTO_AMBIENTALE',
            'VIN_VALUTAZIONE_INCIDENZA',
            'VIN_AREE_AGRICOLE',
            'VIN_UTILIZZO_ACQUE_PUBBLICHE',
            'VIN_VINCOLO_IDROGEOLOGICO',
            'VIN_TUTELA_ACQUE_PUBBLICHE',
            'VIN_TUTELA_AMBIENTI_INQUINAMENTO',
            'VIN_TULP',
            'VIN_LIMITI_ELETTROMAGNETICI',
            'VIN_FASCE_RISPETTO_STRADALE',
            'VIN_FASCE_RISPETTO_FERROVIARIE',
            'VIN_FASCE_RISPETTO_AEROPORTUALE',
            'VIN_FASCE_RISPETTO_CIMITERIALE',
            'VIN_FASCE_RISPETTO_DEPURATORE',
            'VIN_FASCE_RISPETTO_INCIDENTE',
            'VIN_FASCE_RISPETTO_ALTRO',
            'VIN_IMPIANTI_TELECOMUNICAZIONI',
            'VIN_PREVENZIONE_INCENDI',
            'VIN_TULPS_ESERCIZI_PUBBLICI',
            'VIN_VISTO_CORRISPONDENZA',
            'VIN_IMPIANTI_ILLUMINAZIONE_ESTERNA',
            'VIN_VERBALE_ASSEMBLEA',
            'VIN_ASSENSO_COMPROPRIETARI',
            'VIN_ALTRO',
        ],
        Pratica::TYPE_COMUNICAZIONE_INIZIO_LAVORI => [
            'VIN_TULPS_ESERCIZI_PUBBLICI',
            'VIN_IMPIANTI_ILLUMINAZIONE_ESTERNA',
            'VIN_ALTRO',
            'VIN_ALTRO_AMBIENTI',
        ],
        Pratica::TYPE_SEGNALAZIONE_CERTIFICATA_AGIBILITA => [],
        Pratica::TYPE_COMUNICAZIONE_INIZIO_LAVORI_ASSEVERATA => [
          'VIN_TUTELA_PAESAGGISTICA'
        ]
    ];

    public function getProperties()
    {
        return $this->tipoMappedVincoli[$this->tipo] ?? [];
    }

    public function getRequiredFields($tipoIntervento)
    {
        return [];
    }

}
