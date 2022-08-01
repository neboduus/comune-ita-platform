<?php


namespace App\EventListener;


use Symfony\Component\HttpKernel\Event\FilterResponseEvent;

class ResponseListener
{

  private $cacheMaxAge = 0;

  /**
   * ResponseListener constructor.
   */
  public function __construct($cacheMaxAge)
  {
    $this->cacheMaxAge = $cacheMaxAge;
  }

  public function onKernelResponse(FilterResponseEvent $event)
  {
    $response = $event->getResponse();

    $controller = $event->getRequest()->attributes->get('_controller');
    $requiredActions = [
      "App\Controller\Ui\Frontend\DefaultController::commonAction",
      "App\Controller\Ui\Frontend\ServiziController::serviziAction",
      "App\Controller\Ui\Frontend\ServiziController::serviziDetailAction",
      "App\Controller\Ui\Frontend\ServiziController::serviceGroupDetailAction",
      "App\Controller\Ui\Frontend\ServiziController::categoryDetailAction",
      "App\Controller\Rest\ServicesAPIController::getServicesAction",
      "App\Controller\Rest\ServicesAPIController::getServiceAction",
    ];

    if (in_array($controller, $requiredActions) && !empty($this->cacheMaxAge)) {
      $response->headers->addCacheControlDirective('max-age', $this->cacheMaxAge);
      $response->headers->addCacheControlDirective('s-maxage', $this->cacheMaxAge);
      $response->headers->addCacheControlDirective('must-revalidate', true);
      $response->headers->addCacheControlDirective('public', true);
      $response->headers->removeCacheControlDirective('private');
    }
    $event->setResponse($response);
  }

}
