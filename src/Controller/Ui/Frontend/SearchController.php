<?php

namespace App\Controller\Ui\Frontend;

use App\Entity\Servizio;
use App\Services\BreadcrumbsService;
use App\Services\InstanceService;
use App\Services\Manager\ServiceManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class SearchController
 *
 * @package App\Controller
 * @Route("/search")
 */
class SearchController extends AbstractController
{

  /**
   * @var EntityManagerInterface
   */
  private $entityManager;
  /**
   * @var ServiceManager
   */
  private $serviceManager;
  /**
   * @var BreadcrumbsService
   */
  private $breadcrumbsService;

  /** @var InstanceService */
  private $instanceService;

  /**
   * @param EntityManagerInterface $entityManager
   * @param ServiceManager $serviceManager
   * @param BreadcrumbsService $breadcrumbsService
   * @param InstanceService $instanceService
   */
  public function __construct(
    EntityManagerInterface $entityManager,
    ServiceManager $serviceManager,
    BreadcrumbsService $breadcrumbsService,
    InstanceService $instanceService)
  {
    $this->entityManager = $entityManager;
    $this->serviceManager = $serviceManager;
    $this->breadcrumbsService = $breadcrumbsService;
    $this->instanceService = $instanceService;
  }

  /**
   * @Route("/", name="search")
   */
  public function searchAction(Request $request)
  {

    $this->breadcrumbsService->getBreadcrumbs()->addRouteItem('search.label', 'search');

    $services = $this->serviceManager->getServices($request);
    $facets = $this->serviceManager->getFacets();

    $filters = [
      'q' => $request->query->get('q', ''),
      'fields' => []
    ];
    $request->query->remove('q');
    foreach ($request->query->all() as  $v) {
      if (is_array($v)) {
        $filters['fields'] = array_merge($filters['fields'], $v);
      } else {
        $filters['fields'][]= $v;
      }
    }

    return $this->render('Default/search.html.twig', [
      'services' => $services,
      'facets' => $facets,
      'filters' => $filters,
      'ente' => $this->instanceService->getCurrentInstance()
    ]);
  }
}
