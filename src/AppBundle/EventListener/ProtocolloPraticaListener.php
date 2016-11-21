<?php

namespace AppBundle\EventListener;

use AppBundle\Entity\Pratica;
use AppBundle\Event\PraticaOnChangeStatusEvent;
use AppBundle\Logging\LogConstants;
use AppBundle\Services\ProtocolloServiceInterface;
use Psr\Log\LoggerInterface;
use AppBundle\Protocollo\Exception\BaseException as ProtocolloException;

class ProtocolloPraticaListener
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
        if ($event->getNewStateIdentifier() == Pratica::STATUS_SUBMITTED) {
            $pratica = $event->getPratica();
            try {
                $this->protocollo->protocollaPratica($pratica);
            }catch(ProtocolloException $e){
                $this->logger->error(
                    LogConstants::PROTOCOLLO_SEND_ERROR,
                    ['pratica' => $pratica->getId(), 'error_number' => $e->getMessage()]
                );
            }
        }
    }
}
