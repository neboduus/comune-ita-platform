<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class EsploraController
 *
 * @package AppBundle\Controller
 * @Route("/esplora")
 */
class EsploraController extends Controller
{
    /**
     * @Route("/", name="esplora_servizi_list")
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function serviziAction(Request $request)
    {
        return $this->forward('AppBundle:Servizi:servizi');
    }
}
