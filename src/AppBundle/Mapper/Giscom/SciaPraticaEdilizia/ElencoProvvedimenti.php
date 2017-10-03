<?php

namespace AppBundle\Mapper\Giscom\SciaPraticaEdilizia;

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
            'FRT_FASC-VIA',
            'FRT_FASC-PC',
            'FRT_FASC-FER',
            'FRT_FASC-CIM',
            'FRT_FASC-ISO',
            'FRT_FASC-LE',
            'FRT_FASC-DEP',
            'FRT_FASC-AER',
            'FRT_FASC-ALTRO',
            'AGR_SA',
            'AGR_TUR',
            'AGR_ALB',
            'AGR_MANU',
            'PUP_INC',
            'PUP_TULPS',
            'PUP_GEO',
            'PUP_ALB',
            'PUP_RIFU',
            'PUP_ALTRO',
        ];
    }

    public function getRequiredFields($tipoIntervento)
    {
        return [];
    }
}
