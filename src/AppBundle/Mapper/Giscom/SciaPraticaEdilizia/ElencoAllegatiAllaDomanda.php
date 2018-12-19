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
            'DOM_PRIVACY',
            'DOM_ISPAT',
        ];
    }

    public function getRequiredFields($tipoIntervento)
    {
        return $this->getProperties();
    }

}
