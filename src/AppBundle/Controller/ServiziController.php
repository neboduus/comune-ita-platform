<?php

namespace AppBundle\Controller;


use AppBundle\Entity\Ente;
use AppBundle\Entity\ServiceGroup;
use AppBundle\Entity\Servizio;
use AppBundle\Entity\ServizioRepository;
use AppBundle\Handlers\Servizio\ForbiddenAccessException;
use AppBundle\Handlers\Servizio\ServizioHandlerRegistry;
use AppBundle\Logging\LogConstants;
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
    /** @var ServizioRepository $serviziRepository */
    $serviziRepository = $this->getDoctrine()->getRepository('AppBundle:Servizio');
    $stickyServices = $serviziRepository->findStickyAvailable();
    $servizi = $serviziRepository->findNotStickyAvailable();

    $servicesGroupRepository = $this->getDoctrine()->getRepository('AppBundle:ServiceGroup');
    $servicesGroup = $servicesGroupRepository->findAll();

    $services = array();

    /** @var Servizio $item */
    foreach ($servizi as $item) {
      $services[$item->getSlug() . '-' . $item->getId()]['type']= 'service';
      $services[$item->getSlug() . '-' . $item->getId()]['object']= $item;
    }

    /** @var ServiceGroup $item */
    foreach ($servicesGroup as $item) {
      if ($item->getPublicServices()->count() > 0) {
        $services[$item->getSlug() . '-' . $item->getId()]['type']= 'group';
        $services[$item->getSlug() . '-' . $item->getId()]['object']= $item;
      }
    }

    ksort($services);

    return [
      'sticky_services' => $stickyServices,
      'servizi' => $services,
      'user' => $this->getUser()
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
    if (!$servizio instanceof Servizio) {
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

    $handler = $this->get(ServizioHandlerRegistry::class)->getByName($servizio->getHandler());
    $ente = $this->getDoctrine()
      ->getRepository('AppBundle:Ente')
      ->findOneBy(
        [
          'slug' => $this->container->hasParameter('prefix') ? $this->container->getParameter(
            'prefix'
          ) : $request->query->get(PraticheController::ENTE_SLUG_QUERY_PARAMETER, null),
        ]
      );

    if (!$ente instanceof Ente) {
      $this->get('logger')->info(
        LogConstants::PRATICA_WRONG_ENTE_REQUESTED,
        ['headers' => $request->headers]
      );

      throw new \InvalidArgumentException(LogConstants::PRATICA_WRONG_ENTE_REQUESTED);
    }

    $canAccess = true;
    $denyAccessMessage = false;
    try {
      $handler->canAccess($servizio, $ente);
    } catch (ForbiddenAccessException $e) {
      $canAccess = false;
      $denyAccessMessage = $this->get('translator')->trans($e->getMessage(), $e->getParameters());
    }

    return [
      'user' => $user,
      'servizio' => $servizio,
      'servizi_area' => $serviziArea,
      'handler' => $handler,
      'can_access' => $canAccess,
      'deny_access_message' => $denyAccessMessage,
    ];
  }

  /**
   * @Route("/gruppo/{slug}", name="service_group_show")
   * @Template()
   * @param string $slug
   * @param Request $request
   *
   * @return array
   */
  public function serviceGroupDetailAction($slug, Request $request)
  {
    $user = $this->getUser();
    $serviziRepository = $this->getDoctrine()->getRepository('AppBundle:ServiceGroup');

    /** @var Servizio $servizio */
    $servizio = $serviziRepository->findOneBySlug($slug);
    if (!$servizio instanceof ServiceGroup) {
      throw new NotFoundHttpException("ServiceGroup $slug not found");
    }

    return [
      'user' => $user,
      'servizio' => $servizio
    ];
  }

}
