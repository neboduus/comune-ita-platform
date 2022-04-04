<?php

namespace AppBundle\EventListener;

use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Gedmo\Translatable\TranslatableListener;

class LocaleCommandListener
{

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

  public function onConsoleCommand(ConsoleCommandEvent $event)
  {
    // Se abilitato la lingua di default (nel nostro caso it) viene letta e salvata nella tabella delle traduzioni e non nella tabella principale
    // Persist DefaultLocale in translation table
    //$this->translatableListener->setPersistDefaultLocaleTranslation(true);

    // Set currentLocale
    $this->translatableListener->setTranslatableLocale($this->defaultLocale);
  }

}
