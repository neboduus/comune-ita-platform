<?php

namespace AppBundle\Mapper\Giscom\SciaPraticaEdilizia;

class ElencoUlterioriAllegatiTecnici extends AbstractSciaPraticaEdiliziaMappable
{
    const TYPE = 'scia_edilizia_ulteriori_allegati_tecnici';

    public function getProperties()
    {
        return [
            'allegatoB',
            'TEC_PDM',
            'TEC_IMP-EL',
            'TEC_IMP-TER',
            'TEC_IMP-SOL',
            'TEC_IMP-SCAR',
            'TEC_PLARETI',
            'TEC_FIBRA-SCHEMA',
            'TEC_FIBRA-REL',
            'TEC_ENERG-LIM',
            'TEC_ENERG-REQ',
            'TEC_CARAQ',
            'TEC_IMPAQ',
            'TEC_CLIMAQ',
            'TEC_CERTAQ',
            'TEC_SCAVO',
            'TEC_STAT',
            'TEC_CONT-ELAB',
            'TEC_CONT-ESEN',
        ];
    }

    public function getRequiredFields($tipoIntervento)
    {
        return ['allegatoB'];
    }

}
