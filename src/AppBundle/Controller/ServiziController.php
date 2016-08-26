<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Pratica;
use AppBundle\Entity\Servizio;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

/**
 * Class ServiziController
 * @package AppBundle\Controller
 * @Route("/servizi")
 */
class ServiziController extends Controller
{
    /**
     * @Route("/", name="servizi_list")
     * @Template()
     * @param Request $request
     * @return array
     */
    public function serviziAction(Request $request)
    {
        $serviziRepository = $this->getDoctrine()->getRepository('AppBundle:Servizio');
        $servizi = $serviziRepository->findAll();

        return [
            'servizi' => $servizi
        ];
    }

    /**
     * @Route("/{slug}", name="servizi_show")
     * @Template()
     * @param string $slug
     * @param Request $request
     *
     * @return array
     */
    public function serviziDetailAction($slug, Request $request)
    {
        $user = $this->getUser();
        $serviziRepository = $this->getDoctrine()->getRepository('AppBundle:Servizio');
        $servizio = $serviziRepository->findOneBySlug($slug);
        if (!$servizio){
            throw new NotFoundHttpException("Servizio $slug not found");
        }
        $servizi = $serviziRepository->findAll();

        return [
            'user' => $user,
            'servizio' => $servizio,
            'servizi' => $servizi
        ];

    }

}
