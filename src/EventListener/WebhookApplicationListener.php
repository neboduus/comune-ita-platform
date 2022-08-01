<?php

namespace App\EventListener;

use App\BackOffice\BackOfficeInterface;
use App\Entity\DematerializedFormPratica;
use App\Entity\Pratica;
use App\Entity\Webhook;
use App\Event\MessageEvent;
use App\Event\PraticaOnChangeStatusEvent;
use App\ScheduledAction\Exception\AlreadyScheduledException;
use App\Services\ModuloPdfBuilderService;
use App\Services\WebhookService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;
use Psr\Log\LoggerInterface;

class WebhookApplicationListener
{

  /**
   * @var LoggerInterface
   */
  private $logger;
  /**
   * @var EntityManagerInterface
   */
  private $entityManager;
  /**
   * @var WebhookService
   */
  private $webhookService;

  /**
   * WebhookApplicationListener constructor.
   * @param EntityManagerInterface $entityManager
   * @param WebhookService $webhookService
   * @param LoggerInterface $logger
   */
  public function __construct(EntityManagerInterface $entityManager, WebhookService $webhookService, LoggerInterface $logger)
  {
    $this->entityManager = $entityManager;
    $this->webhookService = $webhookService;
    $this->logger = $logger;
  }

  public function onStatusChange(PraticaOnChangeStatusEvent $event)
  {
    $pratica = $event->getPratica();
    $status = $event->getNewStateIdentifier();

    $repo = $this->entityManager->getRepository('App\Entity\Webhook');
    $webhooks = $repo->findBy([
      'trigger' => [$status, Webhook::TRIGGER_ALL],
      'active' => true
    ]);

    if (count($webhooks) > 0 ) {
      foreach ($webhooks as $w) {
        try {
          if ( (Webhook::TRIGGER_ALL === $w->getTrigger() || $status == $w->getTrigger()) &&
               (in_array($pratica->getServizio()->getId(), $w->getFilters()) || in_array(Webhook::TRIGGER_ALL, $w->getFilters()))) {
            $this->webhookService->createApplicationWebhookAsync($pratica, $w);
          }
        } catch (AlreadyScheduledException $e){
          $this->logger->error('Webhook is already scheduled', ['pratica' => $pratica->getId()]);
        } catch (\Exception $e) {
          $this->logger->error('There was an error creating webhook', ['pratica' => $pratica->getId(), 'message' => $e->getMessage()]);
        }
      }
    }
  }

  public function onMessageCreated(MessageEvent $event)
  {
    $message = $event->getItem();
    $pratica = $message->getApplication();
    $repo = $this->entityManager->getRepository('App\Entity\Webhook');
    $webhooks = $repo->findBy([
      'trigger' => [Webhook::TRIGGER_MESSAGE_CREATED, Webhook::TRIGGER_ALL],
      'active' => true
    ]);

    if (count($webhooks) > 0 ) {
      foreach ($webhooks as $w) {
        try {
          if ( (Webhook::TRIGGER_ALL === $w->getTrigger() || Webhook::TRIGGER_MESSAGE_CREATED == $w->getTrigger()) &&
               (in_array($pratica->getServizio()->getId(), $w->getFilters()) || in_array(Webhook::TRIGGER_ALL, $w->getFilters()))) {
            $this->webhookService->createMessageWebhookAsync($message, $w);
          }
        } catch (AlreadyScheduledException $e){
          $this->logger->error('Webhook is already scheduled', ['message' => $message->getId()]);
        } catch (\Exception $e) {
          $this->logger->error('There was an error creating webhook', ['pratica' => $pratica->getId(), 'message' => $e->getMessage()]);
        }
      }
    }
  }
}
