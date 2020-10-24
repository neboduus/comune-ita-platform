<?php

namespace App\Logging;

use App\Entity\Pratica;
use App\Event\PraticaOnChangeStatusEvent;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpFoundation\Request;

class ApplicantBrowserEventResolver
{
  public function getEventLogInfo(Event $event, $eventName)
  {
    if ($event instanceof PraticaOnChangeStatusEvent){
      if ($event->getNewStateIdentifier() == Pratica::STATUS_PRE_SUBMIT){
        return array(
          'description' => '[' . $event->getPratica()->getId() . '] ' . Request::createFromGlobals()->headers->get('User-Agent'),
          'type' => 'applicant.browser',
        );
      }
    }

    return [];
  }

}
