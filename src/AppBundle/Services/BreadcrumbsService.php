<?php


namespace AppBundle\Services;


use AppBundle\Entity\Categoria;
use AppBundle\Entity\ServiceGroup;
use AppBundle\Entity\Servizio;
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
   * BreadcrumbsService constructor.
   * @param Breadcrumbs $breadcrumbs
   * @param RouterInterface $router
   */
  public function __construct(Breadcrumbs $breadcrumbs, RouterInterface $router)
  {
    $this->breadcrumbs = $breadcrumbs;
    $this->breadcrumbs->addRouteItem('Home', 'servizi_list');
    $this->router = $router;
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
    $router = $this->router;
    $this->breadcrumbs->addObjectTree($category, 'name', function($object) use ($router) {
      return $router->generate('category_show', ['slug' => $object->getSlug()]);
    });
  }

  /**
   * @param ServiceGroup $serviceGroup
   */
  public function generateServiceGroupBreadcrumbs(ServiceGroup $serviceGroup)
  {
    if ($serviceGroup->getTopics()) {
      $router = $this->router;
      $this->breadcrumbs->addObjectTree($serviceGroup->getTopics(), 'name', function($object) use ($router) {
        return $router->generate('category_show', ['slug' => $object->getSlug()]);
      });
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
    if ($service->getTopics()) {
      $router = $this->router;
      $this->breadcrumbs->addObjectTree($service->getTopics(), 'name', function($object) use ($router) {
        return $router->generate('category_show', ['slug' => $object->getSlug()]);
      });
    }
    $this->breadcrumbs->addRouteItem($service->getName(), "servizi_show", [
      'slug' => $service->getSlug(),
    ]);
  }

}
