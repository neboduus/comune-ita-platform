<?php

namespace App\Security;

use App\Services\InstanceService;
use App\Services\UserSessionService;
use Artprima\PrometheusMetricsBundle\Metrics\MetricsCollectorInterface;
use Artprima\PrometheusMetricsBundle\Metrics\MetricsGeneratorInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class DedaLoginAuthenticator extends OpenLoginAuthenticator
{
  const LOGIN_ROUTE = 'login_deda';
  /**
   * @var SessionInterface
   */
  private $session;

  /**
   * OpenLoginAuthenticator constructor.
   * @param UrlGeneratorInterface $urlGenerator
   * @param $loginRoute
   * @param UserSessionService $userSessionService
   * @param InstanceService $instanceService
   * @param MetricsCollectorInterface $userMetrics
   * @param JWTTokenManagerInterface $JWTTokenManager
   */
  public function __construct(
    UrlGeneratorInterface     $urlGenerator,
                              $loginRoute,
    UserSessionService        $userSessionService,
    InstanceService           $instanceService,
    MetricsCollectorInterface $userMetrics,
    JWTTokenManagerInterface  $JWTTokenManager,
    SessionInterface          $session
  )
  {
    parent::__construct($urlGenerator, $loginRoute, $userSessionService, $instanceService, $userMetrics, $JWTTokenManager);
    $this->session = $session;
  }

  public function supports(Request $request)
  {
    try {
      $this->checkLoginRoute();
    } catch (\Exception $e) {
      return false;
    }

    if ($this->session->has('DedaLoginUserData')){
      $request->headers->set('X-Forwarded-User', $this->session->get('DedaLoginUserData'));
      $this->session->remove('DedaLoginUserData');
    }
    return $request->attributes->get('_route') === self::LOGIN_ROUTE && $this->checkHeaderUserData($request);
  }

  /**
   * @inheritDoc
   */
  protected function getLoginRouteSupported()
  {
    return [self::LOGIN_ROUTE];
  }
}
