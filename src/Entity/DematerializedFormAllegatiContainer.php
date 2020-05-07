<?php

namespace App\Entity;

interface DematerializedFormAllegatiContainer
{
    /**
     * @return array allegati id list
     */
    public function getAllegatiIdList();

    public function addAllegato(Allegato $allegato);
}
