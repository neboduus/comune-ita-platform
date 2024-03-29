<?php

namespace App\EventListener;

use App\Entity\GiscomPratica;
use App\Entity\Pratica;
use App\Event\PraticaOnChangeStatusEvent;
use App\Protocollo\ProtocolloHandlerInterface;
use App\Protocollo\ProvidersCollection;
use App\ScheduledAction\ScheduledActionHandlerInterface;
use App\Services\PraticaStatusService;
use App\Services\ProtocolloServiceInterface;
use Psr\Log\LoggerInterface;

class ProtocollaPraticaListener
{
  /** @var ProtocolloServiceInterface */
  private ProtocolloServiceInterface $protocollo;

  /** @var PraticaStatusService */
  protected PraticaStatusService $statusService;

  /** @var LoggerInterface */
  private $logger;

  /** @var ProvidersCollection */
  private ProvidersCollection $providersCollection;

  public function __construct(ProtocolloServiceInterface $protocollo, PraticaStatusService $statusService, LoggerInterface $logger, ProvidersCollection $providersCollection)
  {
    $this->protocollo = $protocollo;
    $this->providersCollection = $providersCollection;
    $this->statusService = $statusService;
    $this->logger = $logger;
  }

  public function onStatusChange(PraticaOnChangeStatusEvent $event)
  {

    $pratica = $event->getPratica();
    if ($pratica->getServizio()->isProtocolRequired()) {

      $handler = $this->providersCollection->getHandlerByPratica($pratica);

      $handlerIsExternal = $handler->getExecutionType() == ProtocolloHandlerInterface::PROTOCOL_EXECUTION_TYPE_EXTERNAL;
      //$handlerIsExternal = $this->protocollo->getHandler()->getExecutionType() == ProtocolloHandlerInterface::PROTOCOL_EXECUTION_TYPE_EXTERNAL;

      // Protocollazione esterna!!!!!!
      if ($handlerIsExternal) {
        if ($event->getNewStateIdentifier() == Pratica::STATUS_REGISTERED_AFTER_INTEGRATION) {
          $pratica->getRichiestaDiIntegrazioneAttiva()->markAsDone();
          $this->statusService->setNewStatus($pratica, Pratica::STATUS_PENDING);
          return;
        }

        if ($event->getNewStateIdentifier() == Pratica::STATUS_COMPLETE_WAITALLEGATIOPERATORE || $event->getNewStateIdentifier() == Pratica::STATUS_CANCELLED_WAITALLEGATIOPERATORE) {
          if ($pratica->getEsito()) {
            $this->statusService->setNewStatus($pratica, Pratica::STATUS_COMPLETE);
          } else {
            $this->statusService->setNewStatus($pratica, Pratica::STATUS_CANCELLED);
          }
        }
        return;
      }

      // Protocollazione gestita internamente alla stanza
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

      if ($event->getNewStateIdentifier() == Pratica::STATUS_REGISTERED_AFTER_INTEGRATION && !$pratica instanceof GiscomPratica) {
        $this->statusService->setNewStatus($pratica, Pratica::STATUS_PENDING);
        return;
      }

      if ($event->getNewStateIdentifier() == Pratica::STATUS_COMPLETE_WAITALLEGATIOPERATORE || $event->getNewStateIdentifier() == Pratica::STATUS_CANCELLED_WAITALLEGATIOPERATORE) {
        $this->protocollo->protocollaRisposta($pratica);
        return;
      }

      if ($event->getNewStateIdentifier() == Pratica::STATUS_WITHDRAW) {
        $this->protocollo->protocollaRitiro($pratica);
        return;
      }

    } else {

      if ($event->getNewStateIdentifier() == Pratica::STATUS_REQUEST_INTEGRATION) {
        $this->statusService->setNewStatus($pratica, Pratica::STATUS_DRAFT_FOR_INTEGRATION);
        return;
      }

      if ($event->getNewStateIdentifier() == Pratica::STATUS_REGISTERED_AFTER_INTEGRATION) {
        $pratica->getRichiestaDiIntegrazioneAttiva()->markAsDone();
        $this->statusService->setNewStatus($pratica, Pratica::STATUS_PENDING);
        return;
      }

      if ($event->getNewStateIdentifier() == Pratica::STATUS_SUBMITTED_AFTER_INTEGRATION) {
        $pratica->getRichiestaDiIntegrazioneAttiva()->markAsDone();
        $this->statusService->setNewStatus($pratica, Pratica::STATUS_PENDING);
        return;
      }


      if ($event->getNewStateIdentifier() == Pratica::STATUS_COMPLETE_WAITALLEGATIOPERATORE || $event->getNewStateIdentifier() == Pratica::STATUS_CANCELLED_WAITALLEGATIOPERATORE) {
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
