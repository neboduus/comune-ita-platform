<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\VarDumper\VarDumper;

/**
 * Class ServiziController
 * @package AppBundle\Controller
 * @Route("/servizi")
 */
class ServiziController extends Controller
{
    /**
     * @Route("/", name="servizi_list")
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function serviziAction(Request $request)
    {
        $user = $this->getUser();
        $repo = $this->getDoctrine()->getRepository('AppBundle:Servizio');
        $services = $repo->findAll();

        return $this->render('AppBundle:Default:servizi.html.twig', array('user' => $user, 'services' => $services));
    }
}
