<?php

namespace App\EventListener;


use App\Entity\GiscomPratica;
use App\Event\ProtocollaAllegatiIntegrazioneSuccessEvent;
use App\Event\ProtocollaPraticaSuccessEvent;
use App\Services\GiscomAPIAdapterServiceInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use App\Event\ProtocollaAllegatiOperatoreSuccessEvent;

/**
 * Class GiscomSendPraticaListener
 * @package App\EventListener
 */
class GiscomSendPraticaListener implements EventSubscriberInterface
{
  /**
   * @var GiscomAPIAdapterServiceInterface
   */
  private $giscomAPIAdapterService;

  /**
   * @var LoggerInterface
   */
  private $logger;

  /**
   * GiscomSendPraticaListener constructor.
   * @param GiscomAPIAdapterServiceInterface $giscomAPIAdapterService
   * @param LoggerInterface $logger
   */
  public function __construct(
    GiscomAPIAdapterServiceInterface $giscomAPIAdapterService,
    LoggerInterface                  $logger
  )
  {
    $this->giscomAPIAdapterService = $giscomAPIAdapterService;
    $this->logger = $logger;
  }

  /**
   * @return array
   */
  public static function getSubscribedEvents()
  {
    return [
      ProtocollaPraticaSuccessEvent::class => ['onPraticaProtocollata'],
      ProtocollaAllegatiIntegrazioneSuccessEvent::class => ['onPraticaConIntegrazioniProtocollata'],
      ProtocollaAllegatiOperatoreSuccessEvent::class => ['onPraticaConAllegatiOperatoreProtocollata']
    ];
  }

  /**
   * @param ProtocollaPraticaSuccessEvent $event
   */
  public function onPraticaProtocollata(ProtocollaPraticaSuccessEvent $event)
  {
    $pratica = $event->getPratica();
    if ($pratica instanceof GiscomPratica) {
      $this->giscomAPIAdapterService->sendPraticaToGiscom($pratica);
    }
  }

  public function onPraticaConIntegrazioniProtocollata(ProtocollaPraticaSuccessEvent $event)
  {
    $pratica = $event->getPratica();
    if ($pratica instanceof GiscomPratica) {
      $this->giscomAPIAdapterService->sendPraticaToGiscom($pratica);
    }
  }

  public function onPraticaConAllegatiOperatoreProtocollata(ProtocollaAllegatiOperatoreSuccessEvent $event)
  {
    $pratica = $event->getPratica();
    if ($pratica instanceof GiscomPratica) {
      $this->giscomAPIAdapterService->sendPraticaToGiscom($pratica);
    }
  }
}
