<?php

namespace AppBundle\EventListener;

use AppBundle\BackOffice\BackOfficeInterface;
use AppBundle\Entity\DematerializedFormPratica;
use AppBundle\Entity\Pratica;
use AppBundle\Entity\Webhook;
use AppBundle\Event\MessageEvent;
use AppBundle\Event\PraticaOnChangeStatusEvent;
use AppBundle\ScheduledAction\Exception\AlreadyScheduledException;
use AppBundle\Services\ModuloPdfBuilderService;
use AppBundle\Services\WebhookService;
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

    $repo = $this->entityManager->getRepository('AppBundle:Webhook');
    $webhooks = $repo->findBy([
      'trigger' => [$status, Webhook::TRIGGER_ALL],
      'active' => true
    ]);

    if (count($webhooks) > 0 ) {
      foreach ($webhooks as $w) {
        try {
          if ( ('all' === $w->getTrigger() || $status === $w->getTrigger()) && (in_array($pratica->getServizio()->getId(), $w->getFilters()) || in_array('all', $w->getFilters()))) {
            $this->webhookService->createApplicationWebhookAsync($pratica, $w);
          }
        }catch (AlreadyScheduledException $e){
          $this->logger->error('Webhook is already scheduled', ['pratica' => $pratica->getId()]);
        }
      }
    }
  }

  public function onMessageCreated(MessageEvent $event)
  {
    $message = $event->getItem();
    $pratica = $message->getApplication();
    $repo = $this->entityManager->getRepository('AppBundle:Webhook');
    $webhooks = $repo->findBy([
      'trigger' => [Webhook::TRIGGER_MESSAGE_CREATED, Webhook::TRIGGER_ALL],
      'active' => true
    ]);

    if (count($webhooks) > 0 ) {
      foreach ($webhooks as $w) {
        try {
          if ( (Webhook::TRIGGER_ALL === $w->getTrigger() || Webhook::TRIGGER_MESSAGE_CREATED === $w->getTrigger()) &&
               (in_array($pratica->getServizio()->getId(), $w->getFilters()) || in_array(Webhook::TRIGGER_ALL, $w->getFilters()))) {
            $this->webhookService->createMessageWebhookAsync($message, $w);
          }
        }catch (AlreadyScheduledException $e){
          $this->logger->error('Webhook is already scheduled', ['message' => $message->getId()]);
        }
      }
    }
  }
}
