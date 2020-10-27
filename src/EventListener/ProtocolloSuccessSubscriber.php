<?php

namespace App\EventListener;

use App\Entity\Pratica;
use App\Event\ProtocollaAllegatiIntegrazioneSuccessEvent;
use App\Event\ProtocollaAllegatiOperatoreSuccessEvent;
use App\Event\ProtocollaPraticaSuccessEvent;
use App\Event\ProtocollaRichiesteIntegrazioneSuccessEvent;
use App\Services\PraticaStatusService;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ProtocolloSuccessSubscriber implements EventSubscriberInterface
{
  private $praticaStatusService;

  private $logger;

  public function __construct(PraticaStatusService $praticaStatusService, LoggerInterface $logger)
  {
    $this->praticaStatusService = $praticaStatusService;
    $this->logger = $logger;
  }

  public static function getSubscribedEvents()
  {
    return [
      ProtocollaPraticaSuccessEvent::class => ['onProtocollaPratica'],
      ProtocollaRichiesteIntegrazioneSuccessEvent::class => ['onProtocollaRichiesteIntegrazione'],
      ProtocollaAllegatiIntegrazioneSuccessEvent::class => ['onProtocollaAllegatiIntegrazione'],
      ProtocollaAllegatiOperatoreSuccessEvent::class => ['onProtocollaAllegatiOperatore'],
    ];
  }

  public function onProtocollaPratica(ProtocollaPraticaSuccessEvent $event)
  {
    $this->praticaStatusService->setNewStatus($event->getPratica(), Pratica::STATUS_REGISTERED);
  }

  public function onProtocollaRichiesteIntegrazione(ProtocollaPraticaSuccessEvent $event)
  {
    $this->praticaStatusService->setNewStatus($event->getPratica(), Pratica::STATUS_DRAFT_FOR_INTEGRATION);
  }

  public function onProtocollaAllegatiIntegrazione(ProtocollaPraticaSuccessEvent $event)
  {
    $this->praticaStatusService->setNewStatus($event->getPratica(), Pratica::STATUS_REGISTERED_AFTER_INTEGRATION);
  }

  public function onProtocollaAllegatiOperatore(ProtocollaAllegatiOperatoreSuccessEvent $event)
  {
    $pratica = $event->getPratica();
    if ($pratica->getEsito()) {
      $this->praticaStatusService->setNewStatus($pratica, Pratica::STATUS_COMPLETE);
    } else {
      $this->praticaStatusService->setNewStatus($pratica, Pratica::STATUS_CANCELLED);
    }

  }
}
