<?php

namespace App\Mapper\Giscom\SciaPraticaEdilizia;

class Meta extends AbstractSciaPraticaEdiliziaMappable
{
    const TYPE = 'scia_meta';

    public function getProperties()
    {
        return [
            'DOC_RIC',
            'MODULO_COMPILATO',
            'DOC_ACC',
            'DOC_RIG'
        ];
    }

    public function getRequiredFields($tipoIntervento)
    {
        return [];
    }
}
