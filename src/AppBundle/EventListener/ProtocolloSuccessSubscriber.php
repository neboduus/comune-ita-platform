<?php

namespace AppBundle\EventListener;

use AppBundle\Entity\Pratica;
use AppBundle\Event\ProtocollaAllegatiOperatoreSuccessEvent;
use AppBundle\Event\ProtocollaPraticaSuccessEvent;
use AppBundle\Protocollo\ProtocolloEvents;
use AppBundle\Services\PraticaStatusService;
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
        return[
            ProtocolloEvents::ON_PROTOCOLLA_PRATICA_SUCCESS => ['onProtocollaPratica'],
            ProtocolloEvents::ON_PROTOCOLLA_RICHIESTE_INTEGRAZIONE_SUCCESS => ['onProtocollaRichiesteIntegrazione'],
            ProtocolloEvents::ON_PROTOCOLLA_ALLEGATI_INTEGRAZIONE_SUCCESS => ['onProtocollaAllegatiIntegrazione'],
            ProtocolloEvents::ON_PROTOCOLLA_ALLEGATI_OPERATORE_SUCCESS => ['onProtocollaAllegatiOperatore'],
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
        if ($pratica->getEsito())
        {
            $this->praticaStatusService->setNewStatus($pratica, Pratica::STATUS_COMPLETE);
        }
        else
        {
            $this->praticaStatusService->setNewStatus($pratica, Pratica::STATUS_CANCELLED);
        }

    }
}