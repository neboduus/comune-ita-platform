<?php

namespace AppBundle\EventListener;

use AppBundle\Event\KafkaEvent;
use AppBundle\Services\KafkaService;
use Psr\Log\LoggerInterface;

class KafkaListener
{

  /**
   * @var KafkaService
   */
  private $kafkaService;

  /**
   * @var LoggerInterface
   */
  private $logger;

  /**
   * WebhookApplicationListener constructor.
   * @param KafkaService $kafkaService
   * @param LoggerInterface $logger
   */
  public function __construct(KafkaService $kafkaService, LoggerInterface $logger)
  {
    $this->logger = $logger;
    $this->kafkaService = $kafkaService;
  }

  public function produce(KafkaEvent $event)
  {
    $item = $event->getItem();
    $this->kafkaService->produceMessage($item);
  }
}
