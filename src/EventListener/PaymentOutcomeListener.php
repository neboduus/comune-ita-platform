<?php

namespace App\EventListener;

use App\BackOffice\BackOfficeInterface;
use App\Entity\DematerializedFormPratica;
use App\Entity\Pratica;
use App\Event\PraticaOnChangeStatusEvent;
use App\ScheduledAction\Exception\AlreadyScheduledException;
use App\Services\ModuloPdfBuilderService;
use App\Services\PraticaStatusService;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;
use Psr\Log\LoggerInterface;

class PaymentOutcomeListener
{
  /**
   * @var PraticaStatusService
   */
  private $statusService;

  /**
   * @var LoggerInterface
   */
  private $logger;

  public function __construct(PraticaStatusService $statusService,  LoggerInterface $logger)
  {
    $this->logger = $logger;
    $this->statusService = $statusService;
  }

  public function onStatusChange(PraticaOnChangeStatusEvent $event)
  {
    $application = $event->getPratica();
    if ( $event->getNewStateIdentifier() == Pratica::STATUS_PAYMENT_SUCCESS) {
      // Se la pratica ha già un esito significa che è una pratica con pagamento differito
      if ($application->getEsito()) {
        if ($application->getServizio()->isProtocolRequired()) {
          $this->statusService->setNewStatus($application, Pratica::STATUS_COMPLETE_WAITALLEGATIOPERATORE);
        } else {
          $this->statusService->setNewStatus($application, Pratica::STATUS_COMPLETE);
        }
      } else {
        // Invio la pratica
        $application->setSubmissionTime(time());
        $this->statusService->setNewStatus($application, Pratica::STATUS_PRE_SUBMIT);
      }
    }
  }
}
