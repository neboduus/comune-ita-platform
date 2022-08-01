<?php

namespace App\Mapper\Giscom\SciaPraticaEdilizia;

class ElencoProvvedimenti extends AbstractSciaPraticaEdiliziaMappable
{
    const TYPE = 'scia_edilizia_provvedimenti';

    public function getProperties()
    {
        return [
            'PROV_PC-PAE',
            'PROV_PC-CUL',
            'PROV_AR-VIA',
            'PROV_AR-VI',
            'PROV_AR-PGUAP',
            'PROV_AR-VINIDRO',
            'PROV_AR-ACQP',
            'PROV_AR-ELETTR',
            'PROV_AR-RADIO',
            'PROV_AR-INQ',
            'PROV_AR-SOIS',
            'PROV_AR-APSS',
            'PROV_AR-ILLU',
            'PROV_FRT_FASC-VIA',
            'PROV_FRT_FASC-PC',
            'PROV_FRT_FASC-FER',
            'PROV_FRT_FASC-CIM',
            'PROV_FRT_FASC-ISO',
            'PROV_FRT_FASC-LE',
            'PROV_FRT_FASC-DEP',
            'PROV_FRT_FASC-AER',
            'PROV_FRT_FASC-ALTRO',
            'PROV_AGR_SA',
            'PROV_AGR_TUR',
            'PROV_AGR_ALB',
            'PROV_AGR_MANU',
            'PROV_PUP_INC',
            'PROV_PUP_TULPS',
            'PROV_PUP_GEO',
            'PROV_PUP_ALB',
            'PROV_PUP_RIFU',
            'PROV_PUP_ALTRO',
        ];
    }

    public function getRequiredFields($tipoIntervento)
    {
        return [];
    }
}
