<?php

namespace AppBundle\EventListener;

use AppBundle\Entity\GiscomPratica;
use AppBundle\Entity\Pratica;
use AppBundle\Event\PraticaOnChangeStatusEvent;
use AppBundle\Services\PraticaStatusService;
use AppBundle\Services\ProtocolloServiceInterface;
use Psr\Log\LoggerInterface;

class ProtocollaPraticaListener
{
    /**
     * @var ProtocolloServiceInterface
     */
    private $protocollo;

  /**
   * @var PraticaStatusService
   */
  protected $statusService;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(ProtocolloServiceInterface $protocollo, PraticaStatusService $statusService, LoggerInterface $logger)
    {
        $this->protocollo = $protocollo;
        $this->statusService = $statusService;
        $this->logger = $logger;
    }

    public function onStatusChange(PraticaOnChangeStatusEvent $event)
    {
        $pratica = $event->getPratica();
        if ($pratica->getServizio()->isProtocolRequired()) {

          if ($event->getNewStateIdentifier() == Pratica::STATUS_SUBMITTED) {
            $this->protocollo->protocollaPratica($pratica);
            return;
          }

          if ($event->getNewStateIdentifier() == Pratica::STATUS_REQUEST_INTEGRATION) {
            $this->protocollo->protocollaRichiesteIntegrazione($pratica);
            return;
          }

          if ($event->getNewStateIdentifier() == Pratica::STATUS_SUBMITTED_AFTER_INTEGRATION) {
            $this->protocollo->protocollaAllegatiIntegrazione($pratica);
            return;
          }

          if ( $event->getNewStateIdentifier() == Pratica::STATUS_COMPLETE_WAITALLEGATIOPERATORE || $event->getNewStateIdentifier() == Pratica::STATUS_CANCELLED_WAITALLEGATIOPERATORE ) {
            $this->protocollo->protocollaRisposta($pratica);
            return;
          }

          if ( $event->getNewStateIdentifier() == Pratica::STATUS_WITHDRAW) {
            $this->protocollo->protocollaRitiro($pratica);
            return;
          }

        } else {

          /*if ($event->getNewStateIdentifier() == Pratica::STATUS_SUBMITTED) {
            $this->statusService->setNewStatus($pratica, Pratica::STATUS_PENDING);
            return;
          }*/

          if ($event->getNewStateIdentifier() == Pratica::STATUS_REQUEST_INTEGRATION) {
            $this->statusService->setNewStatus($pratica, Pratica::STATUS_DRAFT_FOR_INTEGRATION);
            return;
          }

          if ($event->getNewStateIdentifier() == Pratica::STATUS_SUBMITTED_AFTER_INTEGRATION) {
            $pratica->getRichiestaDiIntegrazioneAttiva()->markAsDone();
            $this->statusService->setNewStatus($pratica, Pratica::STATUS_PENDING);
            return;
          }

          if ( $event->getNewStateIdentifier() == Pratica::STATUS_COMPLETE_WAITALLEGATIOPERATORE || $event->getNewStateIdentifier() == Pratica::STATUS_CANCELLED_WAITALLEGATIOPERATORE ) {
            if ($pratica->getEsito()) {
              $this->statusService->setNewStatus($pratica, Pratica::STATUS_COMPLETE);
            } else {
              $this->statusService->setNewStatus($pratica, Pratica::STATUS_CANCELLED);
            }
            return;
          }
        }

        return;
    }
}
