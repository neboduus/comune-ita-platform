<?php

namespace AppBundle\Mapper\Giscom\SciaPraticaEdilizia;

class ElencoAllegatiAllaDomanda extends AbstractSciaPraticaEdiliziaMappable
{
    const TYPE = 'scia_ediliza_allegati_modulo_scia';

    public function getProperties()
    {
        return [
            'DOM_CI',
            'DOM_CF',
            'DOM_PAG',
            'DOM_NP',
            'DOM_DURC',
            'DOM_VER',
        ];
    }

    public function getRequiredFields($tipoIntervento)
    {
        return $this->getProperties();
    }

}
