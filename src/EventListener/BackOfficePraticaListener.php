<?php

namespace App\EventListener;

use App\BackOffice\BackOfficeInterface;
use App\Entity\DematerializedFormPratica;
use App\Event\PraticaOnChangeStatusEvent;
use App\Services\BackOfficeCollection;
use Psr\Log\LoggerInterface;

class BackOfficePraticaListener
{
    /**
     * @var BackOfficeCollection
     */
    private $backOfficeCollection;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(BackOfficeCollection $backOfficeCollection, LoggerInterface $logger)
    {
        $this->backOfficeCollection = $backOfficeCollection;
        $this->logger = $logger;
    }

    public function onStatusChange(PraticaOnChangeStatusEvent $event)
    {
        $pratica = $event->getPratica();
        $service = $pratica->getServizio();
        $integrations = $service->getIntegrations();

        if (!empty($integrations) && array_key_exists($event->getNewStateIdentifier(), $integrations) && $pratica instanceof DematerializedFormPratica) {

            /** @var BackOfficeInterface $backOfficeHandler */
            $backOfficeHandler = $this->backOfficeCollection->getBackOffice($integrations[$event->getNewStateIdentifier()]);
            if ($backOfficeHandler instanceof BackOfficeInterface) {
                $backOfficeHandler->execute($pratica);
            } else {
                $this->logger->critical('Backoffice ' . $integrations[$event->getNewStateIdentifier()] . ' not loaded');
            }
        }
    }
}
