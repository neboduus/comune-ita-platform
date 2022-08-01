<?php

namespace App\Form\Base;

use App\Entity\Pratica;
use App\Entity\CPSUser;
use Symfony\Component\HttpFoundation\Request;

interface PraticaFlowInterface
{
    /**
     * @param CPSUser $user
     * @param Pratica $pratica
     */
    public function populatePraticaFieldsWithUserValues(CPSUser $user, $pratica);

    public function getResumeUrl(Request $request);

    public function onFlowCompleted(Pratica $pratica);
}
