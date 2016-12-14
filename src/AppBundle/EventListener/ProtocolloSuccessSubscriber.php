<?php

namespace AppBundle\EventListener;

use AppBundle\Entity\Pratica;
use AppBundle\Protocollo\ProtocolloEvents;
use AppBundle\Event\ProtocollaPraticaSuccessEvent;
use AppBundle\Event\ProtocollaAllegatiOperatoreSuccessEvent;
use AppBundle\Event\ProtocollaAllegatoSuccessEvent;
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
            ProtocolloEvents::ON_PROTOCOLLA_ALLEGATI_OPERATORE_SUCCESS => ['onProtocollaAllegatiOperatore'],
        ];
    }

    public function onProtocollaPratica(ProtocollaPraticaSuccessEvent $event)
    {
        $this->praticaStatusService->setNewStatus($event->getPratica(), Pratica::STATUS_REGISTERED);
    }

    public function onProtocollaAllegatiOperatore(ProtocollaAllegatiOperatoreSuccessEvent $event)
    {
        $this->praticaStatusService->setNewStatus($event->getPratica(), Pratica::STATUS_COMPLETE);
    }
}
