<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Pratica;
use AppBundle\Entity\Servizio;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\JsonResponse;
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
     * @Route("/miller/{topic}", name="servizi_miller", defaults={"topic":false})
     * @Template()
     * @param string $topic
     * @param Request $request
     * @return array
     */
    public function serviziMillerAction($topic, Request $request)
    {
        $topics = $servizi = array();
        $serviziRepository = $this->getDoctrine()->getRepository('AppBundle:Servizio');
        $noSortedTopics = $serviziRepository->createQueryBuilder('t')
            ->select('t.area')
            ->distinct()
            ->getQuery()
            ->getResult();

        foreach ($noSortedTopics as $t)
        {
            $topics []= $t['area'];
        }
        asort($topics);

        if ( !$topic )
        {
            $topic = $topics[0];
        }

        $servizi = $serviziRepository->findBy(
            array('area' => $topic),
            array('name' => 'ASC')
        );

        if ($request->isXMLHttpRequest()) {

            $template = $this->render('@App/Servizi/parts/miller/section.html.twig', ['current_topic' => $topic, 'servizi' => $servizi])->getContent();
            return new JsonResponse(
                ['html' => $template]
            );
        }

        return [
            'current_topic' => $topic,
            'topics'        => $topics,
            'servizi'       => $servizi
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
