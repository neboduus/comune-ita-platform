<?php

namespace App\Services;

use App\Entity\Servizio;
use App\Form\Base\PraticaFlow;
use App\Form\Operatore\Base\PraticaOperatoreFlow;
use Craue\FormFlowBundle\Form\FormFlowInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PraticaFlowRegistry
{
    private $flows = [];

    public function registerFlow(FormFlowInterface $flow, $alias)
    {
        $this->flows[$alias] = $flow;
    }

    /**
     * @param string $alias
     * @return PraticaFlow|PraticaOperatoreFlow
     * @throws NotFoundHttpException
     */
    public function getByName(string $alias)
    {
        if (isset($this->flows[$alias])) {
            return $this->flows[$alias];
        }

        throw new NotFoundHttpException("$alias flow nor found");
    }
}
