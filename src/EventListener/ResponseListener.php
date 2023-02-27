<?php


namespace App\EventListener;


use Symfony\Component\HttpKernel\Event\FilterResponseEvent;

class ResponseListener
{

  private int $cacheMaxAge = 0;
  private ?string $allowedAuthOrigin;

  /**
   * ResponseListener constructor.
   */
  public function __construct($cacheMaxAge, $allowedAuthOrigin)
  {
    $this->cacheMaxAge = $cacheMaxAge;
    $this->allowedAuthOrigin = $allowedAuthOrigin;
  }

  public function onKernelResponse(FilterResponseEvent $event)
  {
    $response = $event->getResponse();
    $request = $event->getRequest();
    $controller = $request->attributes->get('_controller');
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

    $allowedOrigin = '*';
    if ($controller === 'App\Controller\Rest\SessionAuthAPIController::getSessionAuthToken' && $request->query->get('with-cookie', false) && !empty($this->allowedAuthOrigin)) {
      $response->headers->set('Access-Control-Allow-Credentials', 'true');
      $allowedOrigin = $this->allowedAuthOrigin;
    }
    $response->headers->set('Access-Control-Allow-Origin', $allowedOrigin);

    $event->setResponse($response);
  }

}
