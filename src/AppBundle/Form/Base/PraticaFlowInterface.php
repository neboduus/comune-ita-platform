<?php

namespace AppBundle\Form\Base;

use AppBundle\Entity\Pratica;
use AppBundle\Entity\CPSUser;
use Symfony\Component\HttpFoundation\Request;

interface PraticaFlowInterface
{
    /**
     * @param CPSUser $user
     * @param Pratica $pratica
     */
    public function populatePraticaFieldsWithUserValues(CPSUser $user, $pratica);

    /**
     * @param Pratica $lastPratica
     * @param Pratica $pratica
     */
    public function populatePraticaFieldsWithLastPraticaValues($lastPratica, $pratica);


    public function getResumeUrl(Request $request);

    public function onFlowCompleted(Pratica $pratica);
}
