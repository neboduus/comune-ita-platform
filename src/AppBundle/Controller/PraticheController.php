<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Pratica;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\VarDumper\VarDumper;

/**
 * Class PraticheController
 * @package AppBundle\Controller
 * @Route("/pratiche")
 */
class PraticheController extends Controller
{
    /**
     * @Route("/", name="pratiche")
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function praticheAction(Request $request)
    {
        $user = $this->getUser();
        $repo = $this->getDoctrine()->getRepository('AppBundle:Pratica');
        $pratiche = $repo->findBy(
            array('user' => $user),
            array('status' => 'DESC')
        );

        return $this->render('AppBundle:Default:pratiche.html.twig', array('user' => $user, 'pratiche' => $pratiche));
    }
}
