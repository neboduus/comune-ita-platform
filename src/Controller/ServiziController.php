<?php

namespace App\Controller;


use App\Entity\Ente;
use App\Entity\ServiceGroup;
use App\Entity\ServiceGroupRepository;
use App\Entity\Servizio;
use App\Entity\ServizioRepository;
use App\Handlers\Servizio\ForbiddenAccessException;
use App\Handlers\Servizio\ServizioHandlerRegistry;
use App\Logging\LogConstants;
use Doctrine\ORM\EntityRepository;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Translation\TranslatorInterface;


/**
 * Class ServiziController
 * @package App\Controller
 * @Route("/servizi")
 */
class ServiziController extends AbstractController
{
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
   * @param TranslatorInterface $translator
   * @param LoggerInterface $logger
   */
  public function __construct(TranslatorInterface $translator, LoggerInterface $logger)
  {
    $this->logger = $logger;
    $this->translator = $translator;
  }


  /**
   * @Route("/", name="servizi_list")
   * @param Request $request
   * @return array
   */
  public function serviziAction(Request $request)
  {
    /** @var ServizioRepository $serviziRepository */
    $serviziRepository = $this->getDoctrine()->getRepository('App:Servizio');
    /** @var ServiceGroupRepository $servicesGroupRepository */
    $servicesGroupRepository = $this->getDoctrine()->getRepository('App:ServiceGroup');

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

    return $this->render('Servizi/servizi.html.twig', [
      'sticky_services' => $sticky,
      'servizi' => $services,
      'user' => $this->getUser()
    ]);
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
    $serviziRepository = $this->getDoctrine()->getRepository('App:Servizio');

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
      ->getRepository('App:Ente')
      ->findOneBy(
        [
          'slug' => $this->container->hasParameter('prefix') ? $this->container->getParameter(
            'prefix'
          ) : $request->query->get(PraticheController::ENTE_SLUG_QUERY_PARAMETER, null),
        ]
      );

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

    return $this->render('Servizi/serviziDetail.html.twig', [
      'user' => $user,
      'servizio' => $servizio,
      'servizi_area' => $serviziArea,
      'handler' => $handler,
      'can_access' => $canAccess,
      'deny_access_message' => $denyAccessMessage,
    ]);
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
    $serviziRepository = $this->getDoctrine()->getRepository('App:ServiceGroup');

    /** @var Servizio $servizio */
    $servizio = $serviziRepository->findOneBySlug($slug);
    if (!$servizio instanceof ServiceGroup) {
      throw new NotFoundHttpException("ServiceGroup $slug not found");
    }

    return $this->render('Servizi/serviceGroupDetail.html.twig', [
      'user' => $user,
      'servizio' => $servizio
    ]);
  }

  /**
   * Removes a Service from a Service Group.
   * @Route("/{id}/remove_group", name="admin_service_remove_group")
   * @param Request $request
   * @param Servizio $service
   * @return RedirectResponse
   */
  public function removeServiceFromGroup(Request $request, Servizio $service)
  {
    $serviceGroup = $service->getServiceGroup();
    try {
      $em = $this->getDoctrine()->getManager();
      $service->setServiceGroup(null);
      $em->persist($service);
      $em->flush();
      $this->addFlash('feedback', $this->translator->trans('gruppo_di_servizi.servizio_rimosso'));
      return $this->redirectToRoute('admin_service_group_edit', array('id' => $serviceGroup->getId()));

    } catch (\Exception $exception) {
      $this->addFlash('warning', $this->translator->trans('gruppo_di_servizi.errore_rimozione'));
      return $this->redirectToRoute('admin_service_group_edit', array('id' => $serviceGroup->getId()));
    }
  }
}
