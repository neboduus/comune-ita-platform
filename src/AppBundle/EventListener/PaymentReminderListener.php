<?php

namespace AppBundle\EventListener;

use AppBundle\Entity\Pratica;
use AppBundle\Event\PraticaOnChangeStatusEvent;
use AppBundle\ScheduledAction\Exception\AlreadyScheduledException;
use AppBundle\Services\ReminderService;
use Psr\Log\LoggerInterface;

class PaymentReminderListener
{
  /**
   * @var LoggerInterface
   */
  private $logger;

  /**
   * @var ReminderService
   */
  private $reminderService;


  /**
   * @param LoggerInterface $logger
   * @param ReminderService $reminderService
   */
  public function __construct(LoggerInterface $logger, ReminderService $reminderService)
  {
    $this->logger = $logger;
    $this->reminderService = $reminderService;
  }

  public function onStatusChange(PraticaOnChangeStatusEvent $event)
  {
    $pratica = $event->getPratica();
    $newStatus = $event->getNewStateIdentifier();

    if(!($pratica->getServizio()->isPaymentRequired() || $pratica->getServizio()->isPaymentDeferred())) {
      return;
    }

    if ($newStatus != Pratica::STATUS_PAYMENT_PENDING) {
      return;
    }

    try {
      $this->reminderService->createApplicationReminderAsync($pratica);
    } catch (AlreadyScheduledException $e) {
      $this->logger->error('Payment reminder is already scheduled', ['pratica' => $pratica->getId()]);
    }
  }
}
