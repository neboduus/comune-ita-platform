<?php

namespace AppBundle\Controller;


use AppBundle\Entity\Ente;
use AppBundle\Entity\ServiceGroup;
use AppBundle\Entity\ServiceGroupRepository;
use AppBundle\Entity\Servizio;
use AppBundle\Entity\ServizioRepository;
use AppBundle\Handlers\Servizio\ForbiddenAccessException;
use AppBundle\Handlers\Servizio\ServizioHandlerRegistry;
use AppBundle\Logging\LogConstants;
use AppBundle\Services\InstanceService;
use Doctrine\ORM\EntityRepository;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Translation\TranslatorInterface;


/**
 * Class ServiziController
 * @package AppBundle\Controller
 * @Route("/servizi")
 */
class ServiziController extends Controller
{
  /** @var InstanceService */
  private $instanceService;

  /**
   * @var LoggerInterface
   */
  private $logger;
  /**
   * @var TranslatorInterface
   */
  private $translator;

  /**
   * ServiziController constructor.
   * @param InstanceService $instanceService
   * @param TranslatorInterface $translator
   * @param LoggerInterface $logger
   */
  public function __construct(InstanceService $instanceService, TranslatorInterface $translator, LoggerInterface $logger)
  {
    $this->instanceService = $instanceService;
    $this->translator = $translator;
    $this->logger = $logger;
  }


  /**
   * @Route("/", name="servizi_list")
   * @param Request $request
   * @return Response
   */
  public function serviziAction(Request $request)
  {
    /** @var ServizioRepository $serviziRepository */
    $serviziRepository = $this->getDoctrine()->getRepository('AppBundle:Servizio');
    /** @var ServiceGroupRepository $servicesGroupRepository */
    $servicesGroupRepository = $this->getDoctrine()->getRepository('AppBundle:ServiceGroup');

    $stickyServices = $serviziRepository->findStickyAvailable();
    $servizi = $serviziRepository->findNotStickyAvailable();

    $stickyservicesGroup = $servicesGroupRepository->findStickyAvailable();
    $servicesGroup = $servicesGroupRepository->findNotStickyAvailable();

    $services = array();
    $sticky = array();

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

    /** @var Servizio $item */
    foreach ($stickyServices as $item) {
      $sticky[$item->getSlug() . '-' . $item->getId()]['type']= 'service';
      $sticky[$item->getSlug() . '-' . $item->getId()]['object']= $item;
    }

    /** @var ServiceGroup $item */
    foreach ($stickyservicesGroup as $item) {
      if ($item->getPublicServices()->count() > 0) {
        $sticky[$item->getSlug() . '-' . $item->getId()]['type']= 'group';
        $sticky[$item->getSlug() . '-' . $item->getId()]['object']= $item;
      }
    }

    ksort($services);

    $response =  $this->render( '@App/Servizi/servizi.html.twig', [
      'sticky_services' => $sticky,
      'servizi' => $services,
      'user' => $this->getUser()
    ]);

    return $response;
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
   * @param string $slug
   * @param Request $request
   *
   * @return Response
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
    $ente = $this->instanceService->getCurrentInstance();

    if (!$ente instanceof Ente) {
      $this->logger->info(
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
      $denyAccessMessage = $this->translator->trans($e->getMessage(), $e->getParameters());
    }


    $response =  $this->render( '@App/Servizi/serviziDetail.html.twig', [
      'user' => $user,
      'servizio' => $servizio,
      'servizi_area' => $serviziArea,
      'handler' => $handler,
      'can_access' => $canAccess,
      'deny_access_message' => $denyAccessMessage,
    ]);

    return $response;

  }

  /**
   * @Route("/gruppo/{slug}", name="service_group_show")
   * @param string $slug
   * @param Request $request
   *
   * @return Response
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

    $response =  $this->render( '@App/Servizi/serviceGroupDetail.html.twig', [
      'user' => $user,
      'servizio' => $servizio
    ]);

    return $response;
  }
  
}
