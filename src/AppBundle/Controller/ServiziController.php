<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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

    /**
     * @Route("/{slug}", name="servizi_show")
     *
     * @param string $slug
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function serviziDetailAction($slug)
    {
        $user = $this->getUser();
        $repo = $this->getDoctrine()->getRepository('AppBundle:Servizio');
        try{
            $service = $repo->findOneBy(['slug' => $slug]);
        }catch(\Exception $e){
            throw new NotFoundHttpException("Service $slug not found", $e);
        }


        return $this->render('AppBundle:Default:servizio.html.twig', array('user' => $user, 'service' => $service));
    }

    /**
     * @Route("/{slug}/accedi", name="servizi_run")
     *
     * @param string $slug
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function serviziAccediAction($slug)
    {
        return new Response('TODO');
    }
}
