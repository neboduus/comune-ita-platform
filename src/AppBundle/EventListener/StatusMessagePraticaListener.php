<?php

namespace AppBundle\EventListener;

use AppBundle\Entity\Pratica;
use AppBundle\Entity\StatusChange;
use AppBundle\Event\PraticaOnChangeStatusEvent;
use AppBundle\Model\Transition;
use AppBundle\Services\Manager\PraticaManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Translation\TranslatorInterface;

class StatusMessagePraticaListener
{
  /**
   * @var LoggerInterface
   */
  private $logger;

  /**
   * @var PraticaManager
   */
  private $praticaManager;

  /**
   * @var UrlGeneratorInterface
   */
  private $router;

  /**
   * @var TranslatorInterface
   */
  private $translator;

  /**
   * @var EntityManager
   */
  private $entityManager;

  private $blacklistedStates = [
    Pratica::STATUS_REQUEST_INTEGRATION,
    Pratica::STATUS_PROCESSING,
    Pratica::STATUS_SUBMITTED_AFTER_INTEGRATION,
    Pratica::STATUS_DRAFT,
    Pratica::STATUS_DRAFT_FOR_INTEGRATION,
    Pratica::STATUS_COMPLETE_WAITALLEGATIOPERATORE,
    Pratica::STATUS_CANCELLED_WAITALLEGATIOPERATORE,
  ];

  private $blacklistedSDuplicatetates = [
    Pratica::STATUS_PENDING
  ];

  public function __construct(EntityManager $entityManager, PraticaManager $praticaManager, UrlGeneratorInterface $router, TranslatorInterface $translator, LoggerInterface $logger)
  {
    $this->entityManager = $entityManager;
    $this->praticaManager = $praticaManager;
    $this->router = $router;
    $this->translator = $translator;
    $this->logger = $logger;
  }

  public function onStatusChange(PraticaOnChangeStatusEvent $event)
  {
    $pratica = $event->getPratica();
    $newStatus = $event->getNewStateIdentifier();

    $feedbackMessages = $pratica->getServizio()->getFeedbackMessages();

    if (in_array($newStatus, $this->blacklistedStates)) {
      return;
    }

    if (in_array($pratica->getStatus(), $this->blacklistedSDuplicatetates)) {
      // Check if current status exists in application history more than once
      foreach ($pratica->getHistory() as $item) {
        /** @var Transition $item */
        if ($item->getStatusCode() == $pratica->getStatus() && $item->getDate()->getTimestamp() !== $pratica->getLatestStatusChangeTimestamp()) {
          return;
        }
      }
    }

    $placeholders = $this->praticaManager->getPlaceholders($pratica);

    $defaultMessage = $this->translator->trans('messages.pratica.status.' . $newStatus, $placeholders);
    $defaultSubject = $this->translator->trans($this->translator->trans('pratica.email.status_change.subject', $placeholders));
    $generateMessage = false;


    if (!isset($feedbackMessages[$newStatus])) {
      if ($newStatus == Pratica::STATUS_PENDING) {
        // Do not generate default pending message if not enabled
        return;
      }
      // Default status message if no feedback message is set
      $message = $defaultMessage;
      $subject = $defaultSubject;
      $generateMessage = true;
    } else {
      $feedbackMessage = $feedbackMessages[$newStatus];

      // If feedbackmessage is set check if it's enabled
      $generateMessage = $feedbackMessage["isActive"];
      $message = isset($feedbackMessage["message"]) ? strtr($feedbackMessage["message"], $placeholders) : $defaultMessage;
      $subject = isset($feedbackMessage["subject"]) ? strtr($feedbackMessage["subject"], $placeholders) : $defaultSubject;
    }

    if ($generateMessage) {
      try {
        $messageCreated = $this->praticaManager->generateStatusMessage($pratica, $message, $subject);

        // Update application history
        $timestamp = $pratica->getLatestStatusChangeTimestamp();
        /** @var StatusChange $statusChange */

        $statusChange = $pratica->getStoricoStati()->get($timestamp);
        $statusChange[0][1]["message_id"] = $messageCreated->getId();
        $pratica->getStoricoStati()->set($timestamp, $statusChange);

        $this->entityManager->persist($pratica);
        $this->entityManager->flush();
      } catch (ORMException $e) {
        $this->logger->error($e->getMessage() . ' --- ' . $e->getTraceAsString());
      }
    }
  }
}