<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Logout\LogoutSuccessHandlerInterface;

class LogoutSuccessHandler implements LogoutSuccessHandlerInterface
{
  private $router;

  private $singleLogoutUrl;

  public function __construct(UrlGeneratorInterface $router, $singleLogoutUrl)
  {
    $this->router = $router;
    $home = $this->router->generate('home');
    $this->singleLogoutUrl = empty($singleLogoutUrl) ? $home : $singleLogoutUrl;
  }

  public function onLogoutSuccess(Request $request)
  {
    return new RedirectResponse($this->singleLogoutUrl);
  }
}
