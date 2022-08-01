<?php

namespace App\Controller\Ui\Frontend;


use App\Entity\Categoria;
use App\Entity\Ente;
use App\Entity\Recipient;
use App\Entity\ServiceGroup;
use App\Entity\ServiceGroupRepository;
use App\Entity\Servizio;
use App\Entity\ServizioRepository;
use App\Handlers\Servizio\ForbiddenAccessException;
use App\Handlers\Servizio\ServizioHandlerRegistry;
use App\Logging\LogConstants;
use App\Services\BreadcrumbsService;
use App\Services\InstanceService;
use App\Services\Manager\CategoryManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Entity\Repository\CategoryRepository;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Translation\TranslatorInterface;
use WhiteOctober\BreadcrumbsBundle\Model\Breadcrumbs;


/**
 * Class ServiziController
 * @package App\Controller
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
   * @var EntityManagerInterface
   */
  private $entityManager;
  /**
   * @var CategoryManager
   */
  private $categoryManager;
  /**
   * @var BreadcrumbsService
   */
  private $breadcrumbsService;
  /**
   * @var ServizioHandlerRegistry
   */
  private $servizioHandlerRegistry;


  /**
   * ServiziController constructor.
   * @param InstanceService $instanceService
   * @param TranslatorInterface $translator
   * @param EntityManagerInterface $entityManager
   * @param LoggerInterface $logger
   * @param CategoryManager $categoryManager
   * @param BreadcrumbsService $breadcrumbsService
   */
  public function __construct(InstanceService $instanceService, TranslatorInterface $translator, EntityManagerInterface $entityManager, LoggerInterface $logger, CategoryManager $categoryManager, BreadcrumbsService $breadcrumbsService,
                              ServizioHandlerRegistry $servizioHandlerRegistry)
  {
    $this->instanceService = $instanceService;
    $this->translator = $translator;
    $this->entityManager = $entityManager;
    $this->logger = $logger;
    $this->categoryManager = $categoryManager;
    $this->breadcrumbsService = $breadcrumbsService;
    $this->servizioHandlerRegistry = $servizioHandlerRegistry;
  }


  /**
   * @Route("/", name="servizi_list")
   * @param Request $request
   * @return Response
   */
  public function serviziAction(Request $request)
  {

    $this->breadcrumbsService->getBreadcrumbs()->addRouteItem('nav.servizi', 'servizi_list');

    switch ($this->instanceService->getCurrentInstance()->getNavigationType()) {
      case Ente::NAVIGATION_TYPE_CATEGORIES:
        $topics = $this->getServicesByCategories();
        $response = $this->render('@App/Servizi/serviziTopics.html.twig', [
          'topics' => $topics,
          'user' => $this->getUser()
        ]);
        break;

      default:
        $services = $this->getServices($request);
        $response = $this->render('@App/Servizi/servizi.html.twig', [
          'sticky_services' => $services['sticky'],
          'servizi' => $services['default'],
          'user' => $this->getUser()
        ]);
        break;
    }

    return $response;
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
    $serviziRepository = $this->entityManager->getRepository('App:Servizio');

    /** @var Servizio $servizio */
    $servizio = $serviziRepository->findOneBySlug($slug);
    if (!$servizio instanceof Servizio) {
      throw new NotFoundHttpException("Servizio $slug not found");
    }

    $this->breadcrumbsService->generateServiceBreadcrumbs($servizio);

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

    $handler = $this->servizioHandlerRegistry->getByName($servizio->getHandler());
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


    $response = $this->render('@App/Servizi/serviziDetail.html.twig', [
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
    $serviceGroupRepository = $this->getDoctrine()->getRepository('App:ServiceGroup');

    /** @var Servizio $servizio */
    $serviceGroup = $serviceGroupRepository->findOneBySlug($slug);
    if (!$serviceGroup instanceof ServiceGroup) {
      throw new NotFoundHttpException("ServiceGroup $slug not found");
    }

    $this->breadcrumbsService->generateServiceGroupBreadcrumbs($serviceGroup);

    $hasServicesWithMaxResponseTime = $serviceGroupRepository->hasServicesWithMaxResponseTime($serviceGroup->getId());

    $response = $this->render('@App/Servizi/serviceGroupDetail.html.twig', [
      'user' => $user,
      'servizio' => $serviceGroup,
      'hasServicesWithMaxResponseTime' => $hasServicesWithMaxResponseTime
    ]);

    return $response;
  }

  /**
   * @Route("/categoria/{slug}", name="category_show")
   * @param string $slug
   * @param Request $request
   *
   * @return Response
   */
  public function categoryDetailAction($slug, Request $request)
  {

    $user = $this->getUser();
    $categoryRepository = $this->getDoctrine()->getRepository('App:Categoria');

    /** @var Servizio $servizio */
    $category = $categoryRepository->findOneBySlug($slug);
    if (!$category instanceof Categoria) {
      throw new NotFoundHttpException("Category $slug not found");
    }

    $this->breadcrumbsService->generateCategoryBreadcrumbs($category);

    $topics = [];
    $categories = $categoryRepository->findBy(['parent' => null], ['name' => 'asc']);
    /** @var Categoria $c */
    foreach ($categories as $c) {
      if ($this->categoryManager->hasRecursiveRelations($c)) {
        $topics []= $c;
      }
    }

    $children = [];
    /** @var Categoria $c */
    foreach ($category->getChildren() as $c) {
      if ($this->categoryManager->hasRecursiveRelations($c)) {
        $children []= $c;
      }
    }

    $result = [];
    $repoServices = $this->getDoctrine()->getRepository('App:Servizio');
    $services = $repoServices->findAvailable(
      [
        'topics' => $category->getId(),
        'locale' => $request->getLocale()
      ]
    );

    /** @var Servizio $item */
    foreach ($services as $item) {
      $result[$item->getSlug() . '-' . $item->getId()]['type'] = 'service';
      $result[$item->getSlug() . '-' . $item->getId()]['object'] = $item;
    }

    $servicesGroupRepository = $this->getDoctrine()->getRepository('App:ServiceGroup');
    $servicesGroup = $servicesGroupRepository->findByCriteria(
      ['topics' => $category->getId()]
    );

    /** @var ServiceGroup $item */
    foreach ($servicesGroup as $item) {
      if ($item->getPublicServices()->count() > 0) {
        $result[$item->getSlug() . '-' . $item->getId()]['type'] = 'group';
        $result[$item->getSlug() . '-' . $item->getId()]['object'] = $item;
      }
    }
    ksort($services);

    $response = $this->render('@App/Servizi/categoryDetail.html.twig', [
      'user' => $user,
      'category' => $category,
      'categories' => $topics,
      'children' => $children,
      'services' => $result,
    ]);

    return $response;
  }

  /**
   * @Route("/destinatario/{slug}", name="recipient_show")
   * @param string $slug
   * @param Request $request
   *
   * @return Response
   */
  public function recipientDetailAction($slug)
  {
    $user = $this->getUser();
    $recipientRepository = $this->getDoctrine()->getRepository('App:Recipient');

    /** @var Servizio $servizio */
    $recipient = $recipientRepository->findOneBySlug($slug);
    if (!$recipient instanceof Recipient) {
      throw new NotFoundHttpException("Recipient $slug not found");
    }

    $this->breadcrumbsService->getBreadcrumbs()->addRouteItem($recipient->getName(), "recipient_show", [
      'slug' => $slug,
    ]);

    $recipients = $recipientRepository->findBy([], ['name' => 'asc']);

    $repoServices = $this->getDoctrine()->getRepository('App:Servizio');
    $services = $repoServices->findByCriteria([
      'recipients' => $recipient->getId(),
      'status' => Servizio::PUBLIC_STATUSES
    ]);

    $response = $this->render('@App/Servizi/recipientDetail.html.twig', [
      'user' => $user,
      'recipient' => $recipient,
      'recipients' => $recipients,
      'services' => $services
    ]);

    return $response;
  }

  private function getServices(Request $request)
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
      $services[$item->getSlug() . '-' . $item->getId()]['type'] = 'service';
      $services[$item->getSlug() . '-' . $item->getId()]['object'] = $item;
    }

    /** @var ServiceGroup $item */
    foreach ($servicesGroup as $item) {
      if ($item->getPublicServices()->count() > 0) {
        $services[$item->getSlug() . '-' . $item->getId()]['type'] = 'group';
        $services[$item->getSlug() . '-' . $item->getId()]['object'] = $item;
      }
    }

    /** @var Servizio $item */
    foreach ($stickyServices as $item) {
      $sticky[$item->getSlug() . '-' . $item->getId()]['type'] = 'service';
      $sticky[$item->getSlug() . '-' . $item->getId()]['object'] = $item;
    }

    /** @var ServiceGroup $item */
    foreach ($stickyservicesGroup as $item) {
      if ($item->getPublicServices()->count() > 0) {
        $sticky[$item->getSlug() . '-' . $item->getId()]['type'] = 'group';
        $sticky[$item->getSlug() . '-' . $item->getId()]['object'] = $item;
      }
    }

    ksort($services);
    ksort($sticky);

    return [
      'sticky' => $sticky,
      'default' => $services
    ];

  }

  /**
   * @return array
   */
  private function getServicesByCategories()
  {
    $categoryRepository = $this->getDoctrine()->getRepository('App:Categoria');

    $topics = [];
    $categories = $categoryRepository->findBy(['parent' => null], ['name' => 'asc']);
    /** @var Categoria $c */
    foreach ($categories as $c) {
      if ($this->categoryManager->hasRecursiveRelations($c)) {
        $topics[$c->getSlug() . '-' . $c->getId()]['type'] = 'topic';
        $topics[$c->getSlug() . '-' . $c->getId()]['object'] = $c;
      }
    }

    ksort($topics);

    return $topics;

  }

}
