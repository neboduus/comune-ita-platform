<?php


namespace AppBundle\EventListener;


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
      "AppBundle\Controller\DefaultController::commonAction",
      "AppBundle\Controller\ServiziController::serviziAction",
      "AppBundle\Controller\ServiziController::serviziDetailAction",
      "AppBundle\Controller\ServiziController::serviceGroupDetailAction",
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
