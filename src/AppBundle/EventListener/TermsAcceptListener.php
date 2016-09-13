<?php

namespace AppBundle\EventListener;

use AppBundle\Entity\CPSUser;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;

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

    public function __construct(Router $router, TokenStorage $tokenStorage)

    {
        $this->router = $router;
        $this->tokenStorage = $tokenStorage;
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        $user = $this->getUser();
        if ($user instanceof CPSUser) {
            $currentRoute = $event->getRequest()->get('_route');
            $currentRouteParams = $event->getRequest()->get('_route_params');
            $curretRouteQuery = $event->getRequest()->query->all();
            if ($user->getTermsAccepted() == false
                && $currentRoute !== ''
                && $currentRoute !== 'terms_accept'
            ) {
                $redirectParameters['r'] = $currentRoute;
                if ($currentRouteParams) {
                    $redirectParameters['p'] = serialize($currentRouteParams);
                }
                if ($currentRouteParams) {
                    $redirectParameters['q'] = serialize($curretRouteQuery);
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

