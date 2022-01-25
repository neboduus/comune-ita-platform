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
   * WebhookApplicationListener constructor.
   * @param KafkaService $kafkaService
   */
  public function __construct(KafkaService $kafkaService)
  {
    $this->kafkaService = $kafkaService;
  }

  public function produce(KafkaEvent $event)
  {
    $item = $event->getItem();
    $this->kafkaService->produceMessage($item);
  }
}
