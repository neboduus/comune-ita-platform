<?php

namespace App\Entity;


interface GiscomPratica
{
    public function getDematerializedForms();

    public function setRelatedCFs($relatedCFs);

    public function getRelatedCFs();
}
