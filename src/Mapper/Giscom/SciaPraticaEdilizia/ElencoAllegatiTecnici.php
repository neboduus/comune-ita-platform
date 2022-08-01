<?php

namespace App\Mapper\Giscom\SciaPraticaEdilizia;

use App\Entity\Pratica;
use App\Mapper\Giscom\SciaPraticaEdilizia;

class ElencoAllegatiTecnici extends AbstractSciaPraticaEdiliziaMappable
{
    const TYPE = 'scia_edilizia_allegati_tecnici';

    private $tipoMappedFields = [
        Pratica::TYPE_COMUNICAZIONE_OPERE_LIBERE => [
            'OP_DOC_FOTOGRAFICA',
            'OP_PLANIMETRIA',
            'OP_ATTESTAZIONE',
            'OP_ELABORATO_PROGETTUALE',
            'OP_RELAZIONE_DITTA',
            'OP_RELAZIONE_ENERGETICA',
            'OP_RELAZIONE_TECNICA',
            'OP_CATASTO',
            'OP_ALTRO'
        ],

        Pratica::TYPE_COMUNICAZIONE_INIZIO_LAVORI_ASSEVERATA => [
            'OP_DOC_FOTOGRAFICA',
            'OP_PLANIMETRIA_CILA',
            'OP_ELABORATO_PROGETTUALE',
            'OP_RELAZIONE_DITTA',
            'OP_RELAZIONE_ENERGETICA',
            'OP_ALTRO'
        ],

        Pratica::TYPE_SCIA_PRATICA_EDILIZIA => [
            'TEC_ELABORATI_VINCOLI',
            'TEC_DOC_FOTOGRAFICA',
            'TEC_RELAZIONE_TECNICA',
            'TEC_PLANIMETRIE',
            'TEC_SEZ_ALTIMETRICHE',
            'TEC_PIANTE',
            'TEC_SEZIONI',
            'TEC_PROSPETTI',
            'TEC_TECNICA_COSTR',
            'TEC_SPAZI_PARCHEGGIO',
            'TEC_BARRIERE_ARCHITETTONICHE',
            'TEC_RELAZIONE_GEOLOGICA',
            'TEC_STUDIO_COMPATIBILITA',
            'TEC_RELAZIONE_ENERGETICA',
            'TEC_RISULTATI_ANALISI',
            'TEC_COMUNICAZIONI_MATERIA',
            'TEC_PIANO_LAVORO',
            'TEC_IMP-ELET',
            'TEC_IMP-SCAR',
            'TEC_IMP-RADIO',
            'TEC_IMP-CLIMA',
            'TEC_IMP-IDRIC',
            'TEC_IMP-GAS',
            'TEC_IMP-ELEV',
            'TEC_IMP-INCEN',
            'TEC_RETI_SCARIC',
            'TEC_RETI_RIFIU',
            'TEC_RETI_ACQ',
            'TEC_RETI_ELET',
            'TEC_RETI_GAS',
            'TEC_RETI_ALTRO',
            'TEC_COLLEGAMENTO_FIBRA',
            'TEC_DOTAZIONI_RICARICA',
            'TEC_RELAZIONE_ACUSTICHE',
            'TEC_DOC_IMPATTO_ACUSTICO',
            'TEC_DOC_CLIMA_ACUSTICO',
            'OP_ALTRO'
        ],

        Pratica::TYPE_DOMANDA_DI_PERMESSO_DI_COSTRUIRE => [
            'TEC_ELABORATI_VINCOLI',
            'TEC_DOC_FOTOGRAFICA',
            'TEC_RELAZIONE_TECNICA',
            'TEC_PLANIMETRIE',
            'TEC_SEZ_ALTIMETRICHE',
            'TEC_PIANTE',
            'TEC_SEZIONI',
            'TEC_PROSPETTI',
            'TEC_TECNICA_COSTR',
            'TEC_SPAZI_PARCHEGGIO',
            'TEC_BARRIERE_ARCHITETTONICHE',
            'TEC_RELAZIONE_GEOLOGICA',
            'TEC_STUDIO_COMPATIBILITA',
            'TEC_RELAZIONE_ENERGETICA',
            'TEC_RISULTATI_ANALISI',
            'TEC_COMUNICAZIONI_MATERIA',
            'TEC_PIANO_LAVORO',
            'TEC_IMP-ELET',
            'TEC_IMP-SCAR',
            'TEC_IMP-RADIO',
            'TEC_IMP-CLIMA',
            'TEC_IMP-IDRIC',
            'TEC_IMP-GAS',
            'TEC_IMP-ELEV',
            'TEC_IMP-INCEN',
            'TEC_RETI_SCARIC',
            'TEC_RETI_RIFIU',
            'TEC_RETI_ACQ',
            'TEC_RETI_ELET',
            'TEC_RETI_GAS',
            'TEC_RETI_ALTRO',
            'TEC_COLLEGAMENTO_FIBRA',
            'TEC_DOTAZIONI_RICARICA',
            'TEC_RELAZIONE_ACUSTICHE',
            'TEC_DOC_IMPATTO_ACUSTICO',
            'TEC_DOC_CLIMA_ACUSTICO',
            'OP_ALTRO'
        ],

        Pratica::TYPE_DOMANDA_DI_PERMESSO_DI_COSTRUIRE_IN_SANATORIA => [
            'TEC_ELABORATI_VINCOLI',
            'TEC_DOC_FOTOGRAFICA',
            'TEC_RELAZIONE_TECNICA',
            'TEC_PLANIMETRIE',
            'TEC_SEZ_ALTIMETRICHE',
            'TEC_PIANTE',
            'TEC_SEZIONI',
            'TEC_PROSPETTI',
            'TEC_TECNICA_COSTR',
            'TEC_SPAZI_PARCHEGGIO',
            'TEC_BARRIERE_ARCHITETTONICHE',
            'TEC_RELAZIONE_GEOLOGICA',
            'TEC_STUDIO_COMPATIBILITA',
            'TEC_RELAZIONE_ENERGETICA',
            'TEC_RISULTATI_ANALISI',
            'TEC_RETI_SCARIC',
            'TEC_RETI_RIFIU',
            'TEC_RETI_ACQ',
            'TEC_RETI_ELET',
            'TEC_RETI_GAS',
            'TEC_RETI_ALTRO',
            'TEC_COLLEGAMENTO_FIBRA',
            'TEC_DOTAZIONI_RICARICA',
            'TEC_RELAZIONE_ACUSTICHE',
            'TEC_DOC_IMPATTO_ACUSTICO',
            'TEC_DOC_CLIMA_ACUSTICO',
            'OP_ALTRO'
        ],

        Pratica::TYPE_COMUNICAZIONE_INIZIO_LAVORI => [
            'TEC_COMUNICAZIONI_MATERIA',
            'TEC_RETI_SCARIC',
            'TEC_RETI_RIFIU',
            'TEC_RETI_ACQ',
            'TEC_RETI_ELET',
            'TEC_RETI_GAS',
            'TEC_RETI_ALTRO',
            'TEC_COLLEGAMENTO_FIBRA',
            'OP_DOC_FOTOGRAFICA',
            'OP_RELAZIONE_ENERGETICA',
            'OP_NOTIFICA',
            'OP_ALTRO'
        ],

        Pratica::TYPE_DICHIARAZIONE_ULTIMAZIONE_LAVORI => [
            'DUL_NUM_CIVICA',
            'DUL_COPERTURE',
            'DUL_CONF_IMPIANTI',
            'DUL_CERT_ENERGETICA',
            'DUL_ASSEVERA',
            'DUL_CERT_STATICO',
            'DUL_ANTINCENDIO',
            'DUL_CATASTO',
            'DUL_PLANIMETRIE',
            'DUL_TULPS',
            'OP_ALTRO'
        ],

        Pratica::TYPE_AUTORIZZAZIONE_PAESAGGISTICA_SINDACO => [
            'AMB_PRECETTIVI',
            'AMB_DESCRIZIONE',
            'AMB_CONFORMITA',
            'AMB_COMPATIBILITA',
            'AMB_MISURE',
            'OP_ALTRO'
        ],

        Pratica::TYPE_SEGNALAZIONE_CERTIFICATA_AGIBILITA => [
            'TEC_RELAZIONE_ACUSTICHE',
            'OP_ALTRO'
        ]
    ];

    public function getProperties()
    {
        return $this->tipoMappedFields[$this->tipo] ?? [];
    }

    public function getRequiredFields($tipoIntervento)
    {
        switch ($tipoIntervento) {
            case SciaPraticaEdilizia::MANUTENZIONE_STRAORDINARIA:
            case SciaPraticaEdilizia::RESTAURO_E_RISANAMENTO_CONSERVATIVO:
            case SciaPraticaEdilizia::RISTRUTTURAZIONE_EDILIZIA:
            case SciaPraticaEdilizia::RISTRUTTURAZIONE_URBANISTICA:
                return [
                    'TEC_ELABORATI_VINCOLI',
                    'TEC_DOC_FOTOGRAFICA',
                    'TEC_RELAZIONE_TECNICA',
                ];
            case 'default':
                if($this->tipo === Pratica::TYPE_DOMANDA_DI_PERMESSO_DI_COSTRUIRE_IN_SANATORIA ||
                   $this->tipo === Pratica::TYPE_DOMANDA_DI_PERMESSO_DI_COSTRUIRE) {
                    return [
                        'TEC_ELABORATI_VINCOLI',
                        'TEC_DOC_FOTOGRAFICA',
                        'TEC_RELAZIONE_TECNICA',
                    ];
                }
                return [];
            default:
                return [];
        }
    }

}
