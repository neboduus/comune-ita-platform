<?php

namespace AppBundle\EventListener;

use AppBundle\Entity\DematerializedFormPratica;
use AppBundle\Entity\GiscomPratica;
use AppBundle\Event\ProtocollaPraticaSuccessEvent;
use AppBundle\Form\Scia\SciaPraticaEdiliziaFlow;
use AppBundle\Protocollo\ProtocolloEvents;
use AppBundle\Services\GiscomAPIAdapterServiceInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use AppBundle\Services\ScheduleActionService;

/**
 * Class GiscomSendPraticaListener
 * @package AppBundle\EventListener
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
     * @param LoggerInterface         $logger
     */
    public function __construct(
        GiscomAPIAdapterServiceInterface $giscomAPIAdapterService,
        LoggerInterface $logger
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
        return[
            ProtocolloEvents::ON_PROTOCOLLA_PRATICA_SUCCESS => ['onPraticaProtocollata'],
            ProtocolloEvents::ON_PROTOCOLLA_ALLEGATI_INTEGRAZIONE_SUCCESS => ['onPraticaConIntegrazioniProtocollata'],
            ProtocolloEvents::ON_PROTOCOLLA_ALLEGATI_OPERATORE_SUCCESS => ['onPraticaConAllegatiOperatoreProtocollata']
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

    public function onPraticaConAllegatiOperatoreProtocollata(ProtocollaPraticaSuccessEvent $event)
    {
        $pratica = $event->getPratica();
        if ($pratica instanceof GiscomPratica) {
            $this->giscomAPIAdapterService->sendPraticaToGiscom($pratica);
        }
    }
}
