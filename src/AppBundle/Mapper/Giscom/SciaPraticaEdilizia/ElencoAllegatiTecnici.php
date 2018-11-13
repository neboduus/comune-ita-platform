<?php

namespace AppBundle\Mapper\Giscom\SciaPraticaEdilizia;

use AppBundle\Mapper\Giscom\SciaPraticaEdilizia;

class ElencoAllegatiTecnici extends AbstractSciaPraticaEdiliziaMappable
{
    const TYPE = 'scia_edilizia_allegati_tecnici';

    public function getProperties()
    {
        return [
            'TEC_URB',
            'TEC_DF',
            'TEC_RT',
            'TEC_PLAN',
            'TEC_SEZALT',
            'TEC_PIANTE',
            'TEC_SEZ',
            'TEC_PROSP',
            'TEC_CONTRIB',
            'TEC_SCAVO',
            'TEC_AMIANTO',
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
            'TEC_RETI_CUSTOM1',
            'TEC_RETI_CUSTOM2',
            'TEC_RETI_CUSTOM3',
            'TEC_FIBRA',
            'TEC_RICAR',
            'TEC_ACUSTIC',
            'TEC_IMP-ACUSTIC',
            'TEC_CLIMA-ACUSTIC',
        ];
    }

    public function getRequiredFields($tipoIntervento)
    {
        switch ($tipoIntervento) {
            case SciaPraticaEdilizia::MANUTENZIONE_STRAORDINARIA:
            case SciaPraticaEdilizia::RESTAURO_E_RISANAMENTO_CONSERVATIVO:
            case SciaPraticaEdilizia::RISTRUTTURAZIONE_EDILIZIA:
            case SciaPraticaEdilizia::RISTRUTTURAZIONE_URBANISTICA:
                return [
                    'TEC_URB',
                    'TEC_DF',
                    'TEC_RT',
                ];
                break;
            default:
                return [];
        }
    }

}
