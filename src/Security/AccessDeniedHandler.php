<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Authorization\AccessDeniedHandlerInterface;

class AccessDeniedHandler implements AccessDeniedHandlerInterface
{
    private $router;

    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    public function handle(Request $request, AccessDeniedException $accessDeniedException)
    {
        $route = $request->attributes->get('_route');
        $redirect = 'home';

        if (strpos($route, 'operatori') === 0) {
            $redirect = 'operatori_login';
        } elseif (strpos($route, 'admin') === 0) {
            $redirect = 'admin_login';
        }

        return new RedirectResponse($this->router->generate($redirect));
    }

}