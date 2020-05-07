<?php

namespace App\EventListener;

use App\Entity\CPSUser;
use App\Entity\OperatoreUser;
use App\Services\CPSUserProvider;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class CompleteProfileListener
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
     * @var CPSUserProvider
     */
    private $userProvider;

    private $passwordLifeTime;

    /**
     * CompleteProfileListener constructor.
     *
     * @param RouterInterface $router
     * @param TokenStorageInterface $tokenStorage
     * @param CPSUserProvider $userProvider
     * @param string $passwordLifeTime
     */
    public function __construct(RouterInterface $router, TokenStorageInterface $tokenStorage, CPSUserProvider $userProvider, $passwordLifeTime)
    {
        $this->router = $router;
        $this->tokenStorage = $tokenStorage;
        $this->userProvider = $userProvider;
        $this->passwordLifeTime = $passwordLifeTime;
    }

    public function onKernelRequest(RequestEvent $event)
    {
        if ($event->getRequest()->attributes->get('_controller') == 'error_controller'){
            return;
        }

        $user = $this->getUser();
        if ($user instanceof CPSUser) {
            $currentRoute = $event->getRequest()->get('_route');
            $currentRouteParams = $event->getRequest()->get('_route_params');
            $currentRouteQuery = $event->getRequest()->query->all();
            if ($this->userProvider->userHasEnoughData($user) == false
                && $currentRoute !== ''
                && $currentRoute !== null
                && $currentRoute !== 'user_profile'
                && $currentRoute !== 'terms_accept'
            ) {
                $redirectParameters['r'] = $currentRoute;
                if ($currentRouteParams) {
                    $redirectParameters['p'] = serialize($currentRouteParams);
                }
                if ($currentRouteParams) {
                    $redirectParameters['q'] = serialize($currentRouteQuery);
                }

                $redirectUrl = $this->router->generate('user_profile', $redirectParameters);
                $event->setResponse(new RedirectResponse($redirectUrl));
            }
        } elseif ($user instanceof OperatoreUser) {

            // Redirect al operatori_change_password se lastChangePassword è più vecchio della data odierna - $passwordLifeTime
            $currentRoute = $event->getRequest()->get('_route');
            $currentRouteParams = $event->getRequest()->get('_route_params');
            $currentRouteQuery = $event->getRequest()->query->all();

            if (($user->getLastChangePassword() == null
                    || $user->getLastChangePassword()->getTimestamp() < strtotime('-' . $this->passwordLifeTime .' day'))
                && $currentRoute !== 'operatori_change_password') {

                $redirectParameters['r'] = $currentRoute;
                if ($currentRouteParams) {
                    $redirectParameters['p'] = serialize($currentRouteParams);
                }
                if ($currentRouteParams) {
                    $redirectParameters['q'] = serialize($currentRouteQuery);
                }

                $redirectUrl = $this->router->generate('operatori_change_password', $redirectParameters);
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
