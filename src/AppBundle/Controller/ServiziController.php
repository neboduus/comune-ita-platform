<?php

namespace AppBundle\Controller;


use AppBundle\Entity\Servizio;
use Doctrine\ORM\EntityRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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
     * @Template()
     * @param Request $request
     * @return array
     */
    public function serviziAction(Request $request)
    {
        $serviziRepository = $this->getDoctrine()->getRepository('AppBundle:Servizio');
        $stickyServices = $serviziRepository->findBy(
            [
                'sticky' => true,
                'status' => [Servizio::STATUS_AVAILABLE,Servizio::STATUS_SUSPENDED]
            ],
            [
                'name' => 'ASC',
            ]
        );
        $servizi = $serviziRepository->findBy(
            [
                'status' => [Servizio::STATUS_AVAILABLE,Servizio::STATUS_SUSPENDED]
            ],
            [
                'name' => 'ASC',
            ]
        );

        return [
            'sticky_services' => $stickyServices,
            'servizi' => $servizi,
            'user' => $this->getUser(),
        ];
    }

    /**
     * @Route("/miller/{topic}/{subtopic}", name="servizi_miller", defaults={"topic":false, "subtopic":false})
     * @param string $topic
     * @param string $subtopic
     * @param Request $request
     * @return Response|array
     */
    public function serviziMillerAction($topic, $subtopic, Request $request)
    {
        return new Response(null, Response::HTTP_GONE);
    }

    /**
     * @Route("/miller_ajax/{topic}/{subtopic}", name="servizi_miller_ajax", defaults={"subtopic":false})
     * @param string $topic
     * @param string $subtopic
     * @param Request $request
     * @return Response|array
     */
    public function serviziMillerAjaxAction($topic, $subtopic, Request $request)
    {
      return new Response(null, Response::HTTP_GONE);
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

        /** @var EntityRepository $serviziRepository */
        $serviziRepository = $this->getDoctrine()->getRepository('AppBundle:Servizio');

        /** @var Servizio $servizio */
        $servizio = $serviziRepository->findOneBySlug($slug);
        if (!$servizio instanceof Servizio){
            throw new NotFoundHttpException("Servizio $slug not found");
        }

        $serviziArea = $serviziRepository->createQueryBuilder('servizio')
          ->andWhere('servizio.id != :servizio')
          ->setParameter('servizio', $servizio->getId())

          ->andWhere('servizio.ente IN (:ente)')
          ->setParameter('ente', $servizio->getEnte())

          ->andWhere('servizio.status = :status')
          ->setParameter('status', Servizio::STATUS_AVAILABLE)

          ->andWhere('servizio.topics in (:topics)')
          ->setParameter('topics', $servizio->getTopics())

          ->orderBy('servizio.name', 'asc')
          ->setMaxResults(5)

          ->getQuery()->execute();

        $handler = null;
        if ($servizio->getHandler() != null && !empty($servizio->getHandler()) && $servizio->getHandler() != 'default') {
            $handler = $this->get($servizio->getHandler());
        }

        return [
            'user'         => $user,
            'servizio'     => $servizio,
            'servizi_area' => $serviziArea,
            'handler'      => $handler
        ];
    }

}
