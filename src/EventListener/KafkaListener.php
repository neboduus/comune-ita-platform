<?php

namespace App\EventListener;

use App\Event\KafkaEvent;
use App\Services\KafkaService;
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

  /**
   * @param KafkaEvent $event
   * @return void
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function produce(KafkaEvent $event)
  {
    // Todo: è necessario un try catch?
    $item = $event->getItem();
    $this->kafkaService->produceMessage($item);
  }
}
