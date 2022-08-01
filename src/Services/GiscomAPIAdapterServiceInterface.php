<?php

namespace App\Services;

use App\Entity\GiscomPratica;

interface GiscomAPIAdapterServiceInterface
{
    /**
     * @param GiscomPratica $pratica
     */
    public function sendPraticaToGiscom(GiscomPratica $pratica);

    /**
     * @param GiscomPratica $pratica
     */
    public function askRelatedCFsForPraticaToGiscom(GiscomPratica $pratica);
}
