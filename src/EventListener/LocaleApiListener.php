<?php

namespace App\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpFoundation\Request;
use Gedmo\Translatable\TranslatableListener;

class LocaleApiListener
{

  private $currentLocale = false;
  private $translatableListener;
  private $defaultLocale;
  private $availableLocales;


  /**
   * @param $defaultLocale
   * @param $availableLocales
   * @param TranslatableListener $translatableListener
   */
  public function __construct($defaultLocale, $availableLocales, TranslatableListener $translatableListener)
  {
    $this->translatableListener = $translatableListener;
    $this->defaultLocale = $defaultLocale;
    $this->availableLocales = explode('|', $availableLocales);
  }

  public function onKernelRequest(GetResponseEvent $event)
  {
    // Se abilitato la lingua di default (nel nostro caso it) viene letta e salvata nella tabella delle traduzioni e non nella tabella principale
    // Persist DefaultLocale in translation table
    // $this->translatableListener->setPersistDefaultLocaleTranslation(true);

    $request = $event->getRequest();

    if (strpos($request->getRequestUri(), '/api/') == true) {
      if ($request->headers->has("x-locale")) {
        $locale = $request->headers->get('x-locale');
        if (in_array($locale, $this->availableLocales)) {
          $request->setLocale($locale);
        } else {
          $request->setLocale($this->defaultLocale);
        }
      } else {
        $request->setLocale($this->defaultLocale);
      }
    }

    // Set currentLocale
    $this->translatableListener->setTranslatableLocale($request->getLocale());
    $this->currentLocale = $request->getLocale();
  }

  /**
   * @param FilterResponseEvent $event
   */
  public function onKernelResponse(FilterResponseEvent $event)
  {
    if ($this->currentLocale) {
      $response = $event->getResponse();
      $response->headers->add(array('Content-Language' => $this->currentLocale));
      $event->setResponse($response);
    }
  }
}
