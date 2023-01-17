<?php

namespace App\EventListener;

use Symfony\Component\HttpFoundation\RedirectResponse;

use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Matcher\UrlMatcherInterface;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouterInterface;

class LocaleRewriteListener
{

  private $router;

  /**
   * @var string
   */
  private $defaultLocale;

  /**
   * @var UrlMatcherInterface
   */
  private $matcher;

  /**
   * @var string
   */
  private $prefix;

  public function __construct(RouterInterface $router, UrlMatcherInterface $matcher, $prefix, $defaultLocale)
  {
    $this->router = $router;
    $this->defaultLocale = $defaultLocale;
    $this->matcher = $matcher;
    $this->prefix = $prefix;
  }

  public function isLocaleSupported($locale)
  {
    return in_array($locale, $this->supportedLocales);
  }

  public function onKernelRequest(GetResponseEvent $event)
  {

    $request = $event->getRequest();
    $path = $request->getPathInfo();

    if ( $request->getRequestUri() === '/' ) {
      return;
    }

    if (strpos($request->getRequestUri(), '/api/') == true) {
      return;
    }

    if (!$request->attributes->has('_locale') && $request->attributes->has('exception')) {

      $redirectUrl = str_replace($this->prefix, $this->prefix . '/' . $this->defaultLocale, $path);

      try {
        $mathResult = $this->matcher->match($redirectUrl);
        $route = $mathResult['_route'];
        unset($mathResult['_route'], $mathResult['_controller']);
        $event->setResponse(new RedirectResponse($this->router->generate($route, $mathResult), 307));

      } catch (\Exception $e) {
        // No log exception
      }
    }
  }

}
