<?php

namespace AppBundle\EventListener;

use AppBundle\Entity\Pratica;
use AppBundle\Event\PraticaOnChangeStatusEvent;
use AppBundle\Logging\LogConstants;
use AppBundle\Services\ProtocolloServiceInterface;
use Psr\Log\LoggerInterface;
use AppBundle\Protocollo\Exception\BaseException as ProtocolloException;

class ProtocollaPraticaListener
{
    /**
     * @var ProtocolloServiceInterface
     */
    private $protocollo;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(ProtocolloServiceInterface $protocollo, LoggerInterface $logger)
    {
        $this->protocollo = $protocollo;
        $this->logger = $logger;
    }

    public function onStatusChange(PraticaOnChangeStatusEvent $event)
    {
        $pratica = $event->getPratica();
        if ($event->getNewStateIdentifier() == Pratica::STATUS_SUBMITTED) {
            $this->protocollo->protocollaPratica($pratica);

            return;
        }

        if ($event->getNewStateIdentifier() == Pratica::STATUS_COMPLETE_WAITALLEGATIOPERATORE) {
            $this->protocollo->protocollaAllegatiOperatore($pratica);

            return;
        }

        return;
    }
}
