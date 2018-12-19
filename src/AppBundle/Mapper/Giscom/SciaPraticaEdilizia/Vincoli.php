<?php

namespace AppBundle\Mapper\Giscom\SciaPraticaEdilizia;

class Vincoli extends AbstractSciaPraticaEdiliziaMappable
{
    const TYPE = 'scia_edilizia_ulteriori_allegati_tecnici';

    public function getProperties()
    {
        return [
            'VIN_PAES',
            'VIN_CULT',
            'VIN_IMPAMB',
            'VIN_AREEPROT',
            'VIN_AGRI-PUP',
            'VIN_PGUAP',
            'VIN_IDRO',
            'VIN_ACQ-PUB',
            'VIN_INQUI',
            'VIN_TULP',
            'VIN_ELETTRO',
            'VIN_RISP-STRA',
            'VIN_RISP-FERR',
            'VIN_RISP-AERO',
            'VIN_RISP-CIMI',
            'VIN_RISP-DEPUR',
            'VIN_RISP-INCID',
            'VIN_RISP-ALTRO',
            'VIN_TELE',
            'VIN_INCEND',
            'VIN_TULPS',
            'VIN_7-2002',
            'VIN_ILLUM',
            'VIN_CUSTOM1',
            'VIN_CUSTOM2',

        ];
    }

    public function getRequiredFields($tipoIntervento)
    {
        return [];
    }

}
