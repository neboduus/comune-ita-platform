<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Pratica;
use AppBundle\Entity\Servizio;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

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

        //TODO: query con join
        /*$em = $this->getDoctrine()->getEntityManager();
        $query = $em->createQuery(
            'SELECT s
            FROM AppBundle:Pratica p
            WHERE p.user :user
            ORDER BY p.creationTime ASC'
        )->setParameters(array('user' => $user, 'status' => Pratica::STATUS_PENDING));

        $serviziPending = $query->getResult();*/

        $praticheRepository = $this->getDoctrine()->getRepository('AppBundle:Pratica');
        $pratiche = $praticheRepository->findBy(
            array('user' => $user, 'status' => Pratica::STATUS_PENDING),
            array('creationTime' => 'ASC')
        );

        $serviziPending = array();
        /** @var Pratica $p */
        foreach ($pratiche as $p)
        {
            $serviziPending []= $p->getServizio();
        }

        $serviziRepository = $this->getDoctrine()->getRepository('AppBundle:Servizio');
        $servizi = $serviziRepository->findAll();

        return $this->render('AppBundle:Default:servizi.html.twig', array('user' => $user, 'servizi_pending' => $serviziPending, 'servizi' => $servizi));
    }

    /**
     * @Route("/{slug}", name="servizi_show")
     *
     * @param string $slug
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function serviziDetailAction($slug, Request $request)
    {
        $user = $this->getUser();
        $serviziRepository = $this->getDoctrine()->getRepository('AppBundle:Servizio');
        $servizio = $serviziRepository->findOneBySlug($slug);
        if (!$servizio){
            throw new NotFoundHttpException("Servizio $slug not found");
        }

        return $this->render('AppBundle:Default:servizio.html.twig', array(
            'user' => $user,
            'servizio' => $servizio
        ));

    }

}
