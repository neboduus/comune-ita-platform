<?php

namespace AppBundle\Mapper\Giscom\SciaPraticaEdilizia;

class ElencoAllegatiTecnici extends AbstractSciaPraticaEdiliziaMappable
{
    const TYPE = 'scia_edilizia_allegati_tecnici';

    public function getProperties()
    {
        return [
            'TEC_URB',
            'TEC_DF',
            'TEC_RT',
            'TEC_PLANFAT',
            'TEC_PLANPROG',
            'TEC_PLANRAF',
            'TEC_SEZPLANRAF',
            'TEC_PIANTEFAT',
            'TEC_PIANTEPROG',
            'TEC_PIANTERAF',
            'TEC_SEZFAT',
            'TEC_SERPROG',
            'TEC_SEZRAF',
            'TEC_PROSPFAT',
            'TEC_PROSPPROG',
            'TEC_PROSPRAF',
            'TEC_BARARC',
            'TEC_SPAZIPARC',
            'TEC_GEO',
            'TEC_ENER',
        ];
    }

    public function getRequiredFields($tipoIntervento)
    {
        switch ($tipoIntervento) {
            case 'nuova':
                return [
                    'TEC_URB',
                    'TEC_DF',
                    'TEC_RT',
                    'TEC_PLANFAT',
                    'TEC_PLANPROG',
                    'TEC_PLANRAF',
                    'TEC_SEZPLANRAF',
                    'TEC_PIANTEFAT',
                    'TEC_SERPROG',
                    'TEC_PROSPPROG',
                    'TEC_BARARC',
                    'TEC_SPAZIPARC',
                    'TEC_GEO',
                    'TEC_ENER'
                ];

            case 'ampliamento':
                return [
                    'TEC_URB',
                    'TEC_DF',
                    'TEC_RT',
                    'TEC_SEZFAT',
                    'TEC_SERPROG',
                    'TEC_SEZRAF',
                    'TEC_PROSPFAT',
                    'TEC_PROSPPROG',
                    'TEC_PROSPRAF',
                    'TEC_BARARC',
                    'TEC_SPAZIPARC',
                    'TEC_GEO',
                ];

            case 'demolizione':
                return [
                    'TEC_URB',
                    'TEC_DF',
                    'TEC_RT',
                    'TEC_PLANFAT',
                    'TEC_PLANPROG',
                    'TEC_PLANRAF',
                    'TEC_SEZPLANRAF',
                    'TEC_PIANTEFAT',
                    'TEC_SEZFAT',
                    'TEC_SERPROG',
                    'TEC_PROSPPROG',
                    'TEC_SPAZIPARC',
                    'TEC_GEO',
                    'TEC_ENER',
                ];

            case 'straordinaria':
                return [
                    'TEC_DF',
                    'TEC_RT',
                ];

            case 'cambio_uso':
                return [
                    'TEC_DF',
                    'TEC_RT',
                    'TEC_SEZFAT',
                    'TEC_SERPROG',
                    'TEC_SPAZIPARC',
                    'TEC_GEO'
                ];

            default:
                return [];
        }
    }

}
