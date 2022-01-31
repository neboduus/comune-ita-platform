<?php

namespace AppBundle\Controller\Ui\Frontend;

use AppBundle\Entity\Servizio;
use AppBundle\Services\BreadcrumbsService;
use AppBundle\Services\Manager\ServiceManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class SearchController
 *
 * @package AppBundle\Controller
 * @Route("/search")
 */
class SearchController extends Controller
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

  /**
   * @param EntityManagerInterface $entityManager
   * @param ServiceManager $serviceManager
   * @param BreadcrumbsService $breadcrumbsService
   */
  public function __construct(EntityManagerInterface $entityManager, ServiceManager $serviceManager, BreadcrumbsService $breadcrumbsService)
  {
    $this->entityManager = $entityManager;
    $this->serviceManager = $serviceManager;
    $this->breadcrumbsService = $breadcrumbsService;
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
      'search_text' => $request->query->get('search_text', ''),
      'fields' => []
    ];
    $request->query->remove('search_text');
    foreach ($request->query->all() as  $v) {
      if (is_array($v)) {
        $filters['fields'] = array_merge($filters['fields'], $v);
      } else {
        $filters['fields'][]= $v;
      }
    }

    return $this->render('@App/Default/search.html.twig', [
      'services' => $services,
      'facets' => $facets,
      'filters' => $filters
    ]);
  }
}
