<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class DefaultController
 *
 * @package AppBundle\Controller
 */
class DefaultController extends Controller
{
    /**
     * @Route("/")
     */
    public function indexAction(Request $request)
    {
        $user = $this->getUser();

        return $this->render('AppBundle:Default:index.html.twig', array('user' => $user));
    }

    /**
     * @Route("/servizi")
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function serviziAction(Request $request)
    {
        $user = $this->getUser();

        return $this->render('AppBundle:Default:servizi.html.twig', array('user' => $user));
    }
}
