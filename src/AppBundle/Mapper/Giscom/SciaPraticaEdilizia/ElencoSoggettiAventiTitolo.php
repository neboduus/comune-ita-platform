<?php

namespace AppBundle\Mapper\Giscom\SciaPraticaEdilizia;

use AppBundle\Mapper\Giscom\FileCollection;

class ElencoSoggettiAventiTitolo extends FileCollection
{
    const TYPE = 'scia_ediliza_soggetti';

    public function isRequired()
    {
        return true;
    }
}
