<?php

namespace App\Mapper\Giscom\SciaPraticaEdilizia;

use App\Mapper\Giscom\FileCollection;

class ElencoSoggettiAventiTitolo extends FileCollection
{
    const TYPE = 'scia_ediliza_soggetti';

    public function isRequired()
    {
        return true;
    }
}
