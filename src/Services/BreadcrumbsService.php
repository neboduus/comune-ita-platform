<?php


namespace App\Services;


use App\Entity\Categoria;
use App\Entity\ServiceGroup;
use App\Entity\Servizio;
use Symfony\Component\Routing\RouterInterface;
use WhiteOctober\BreadcrumbsBundle\Model\Breadcrumbs;

class BreadcrumbsService
{
  /**
   * @var Breadcrumbs
   */
  private $breadcrumbs;
  /**
   * @var RouterInterface
   */
  private $router;
  /**
   * @var InstanceService
   */
  private $instanceService;

  /**
   * BreadcrumbsService constructor.
   * @param Breadcrumbs $breadcrumbs
   * @param RouterInterface $router
   * @param InstanceService $instanceService
   */
  public function __construct(Breadcrumbs $breadcrumbs, RouterInterface $router, InstanceService $instanceService)
  {
    $this->breadcrumbs = $breadcrumbs;
    $this->router = $router;
    $this->instanceService = $instanceService;
    $this->init();

  }

  private function init()
  {
    if ($this->instanceService->getCurrentInstance()->getSiteUrl()) {
      $this->breadcrumbs->addItem('Home', $this->instanceService->getCurrentInstance()->getSiteUrl());
    }

  }

  /**
   * @return Breadcrumbs
   */
  public function getBreadcrumbs(): Breadcrumbs
  {
    return $this->breadcrumbs;
  }

  /**
   * @param Categoria $category
   */
  public function generateCategoryBreadcrumbs(Categoria $category)
  {
    $this->breadcrumbs->addRouteItem('nav.servizi', 'servizi_list');
    $router = $this->router;
    $this->breadcrumbs->addObjectTree($category, 'name', function($object) use ($router) {
      return $router->generate('category_show', ['slug' => $object->getSlug()]);
    }, 'parent', [], 2);
  }

  /**
   * @param ServiceGroup $serviceGroup
   */
  public function generateServiceGroupBreadcrumbs(ServiceGroup $serviceGroup)
  {
    $this->breadcrumbs->addRouteItem('nav.servizi', 'servizi_list');
    if ($serviceGroup->getTopics()) {
      $router = $this->router;
      $this->breadcrumbs->addObjectTree($serviceGroup->getTopics(), 'name', function($object) use ($router) {
        return $router->generate('category_show', ['slug' => $object->getSlug()]);
      }, 'parent', [], 2);
    }
    $this->breadcrumbs->addRouteItem($serviceGroup->getName(), "service_group_show", [
      'slug' => $serviceGroup->getSlug(),
    ]);
  }

  /**
   * @param Servizio $service
   */
  public function generateServiceBreadcrumbs(Servizio $service)
  {
    $this->breadcrumbs->addRouteItem('nav.servizi', 'servizi_list');
    if ($service->getServiceGroup() && $service->isSharedWithGroup()) {
      $this->generateServiceGroupBreadcrumbs($service->getServiceGroup());
    } else {
      if ($service->getTopics()) {
        $router = $this->router;
        $this->breadcrumbs->addObjectTree($service->getTopics(), 'name', function($object) use ($router) {
          return $router->generate('category_show', ['slug' => $object->getSlug()]);
        }, 'parent', [], 2);
      }
      if ($service->getServiceGroup()) {
        $this->breadcrumbs->addRouteItem($service->getServiceGroup()->getName(), "service_group_show", [
          'slug' => $service->getServiceGroup()->getSlug(),
        ]);
      }
      $this->breadcrumbs->addRouteItem($service->getName(), "servizi_show", [
        'slug' => $service->getSlug(),
      ]);
    }
  }

}
