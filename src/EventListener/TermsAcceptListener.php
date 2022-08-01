<?php

namespace App\EventListener;

use App\Entity\CPSUser;
use App\Services\TermsAcceptanceCheckerService;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;

/**
 * Class TermsAcceptListener
 */
class TermsAcceptListener
{

  /**
   * @var Router
   */
  private $router;

  /**
   * @var TokenStorage
   */
  private $tokenStorage;

  /**
   * @var TermsAcceptanceCheckerService
   */
  private $termsAcceptanceChecker;

  /**
   * TermsAcceptListener constructor.
   * @param Router $router
   * @param TokenStorage $tokenStorage
   */
  public function __construct(Router $router, TokenStorage $tokenStorage, TermsAcceptanceCheckerService $termsAcceptanceChecker)
  {
    $this->router = $router;
    $this->tokenStorage = $tokenStorage;
    $this->termsAcceptanceChecker = $termsAcceptanceChecker;
  }

  /**
   * @param GetResponseEvent $event
   */
  public function onKernelRequest(GetResponseEvent $event)
  {
    $user = $this->getUser();
    if ($user instanceof CPSUser) {
      $currentRoute = $event->getRequest()->get('_route');
      $currentRouteParams = $event->getRequest()->get('_route_params');
      $currentRouteQuery = $event->getRequest()->query->all();

      if (!$this->termsAcceptanceChecker->checkIfUserHasAcceptedMandatoryTerms($user)
        && !empty($currentRoute)
        && $currentRoute !== 'terms_accept'
      ) {

        $redirectParameters['r'] = $currentRoute;
        if ($currentRouteParams) {
          $redirectParameters['p'] = serialize($currentRouteParams);
        }
        if ($currentRouteParams) {
          $redirectParameters['q'] = serialize($currentRouteQuery);
        }

        $redirectUrl = $this->router->generate('terms_accept', $redirectParameters);
        $event->setResponse(new RedirectResponse($redirectUrl));
      }
    }
  }

  protected function getUser()
  {
    if (null === $token = $this->tokenStorage->getToken()) {
      return null;
    }

    if (!is_object($user = $token->getUser())) {
      return null;
    }

    return $user;
  }
}
