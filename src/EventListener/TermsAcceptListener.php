<?php

namespace App\EventListener;

use App\Entity\CPSUser;
use App\Services\TermsAcceptanceCheckerService;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Class TermsAcceptListener
 */
class TermsAcceptListener
{

    /** @var RouterInterface */
    private $router;

    /** @var TokenStorageInterface */
    private $tokenStorage;

    /** @var TermsAcceptanceCheckerService */
    private $termsAcceptanceChecker;

  /**
   * TermsAcceptListener constructor.
   * @param RouterInterface $router
   * @param TokenStorageInterface $tokenStorage
   * @param TermsAcceptanceCheckerService $termsAcceptanceChecker
   */
    public function __construct(RouterInterface $router, TokenStorageInterface $tokenStorage, TermsAcceptanceCheckerService $termsAcceptanceChecker)
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
            if ($this->termsAcceptanceChecker->checkIfUserHasAcceptedMandatoryTerms($user) == false
                && $currentRoute !== ''
                && $currentRoute !== 'terms_accept'
                && $currentRoute !== 'user_profile'
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
