<?php

namespace AppBundle\Services;

use AppBundle\Entity\GiscomPratica;

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
