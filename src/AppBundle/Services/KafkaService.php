<?php


namespace AppBundle\Services;


use AppBundle\Dto\Application;
use AppBundle\Dto\Service;
use AppBundle\Entity\Pratica;
use AppBundle\Entity\ScheduledAction;
use AppBundle\Entity\Servizio;
use AppBundle\ScheduledAction\Exception\AlreadyScheduledException;
use AppBundle\ScheduledAction\ScheduledActionHandlerInterface;
use DateTime;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use JMS\Serializer\SerializerInterface;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;


class KafkaService implements ScheduledActionHandlerInterface
{

  const ACTION_PRODUCE_MESSAGE = 'produce_message';

  /**
   * @var ScheduleActionService
   */
  private $scheduleActionService;

  /**
   * @var RouterInterface
   */
  private $router;
  /**
   * @var SerializerInterface
   */
  private $serializer;
  /**
   * @var VersionService
   */
  private $versionService;
  /**
   * @var LoggerInterface
   */
  private $logger;

  private $kafkaUrl;

  private $kafkaEventVersion;
  private $topics;
  /**
   * @var FormServerApiAdapterService
   */
  private $formServerApiAdapterService;

  /**
   * WebhookService constructor.
   * @param ScheduleActionService $scheduleActionService
   * @param RouterInterface $router
   * @param SerializerInterface $serializer
   * @param VersionService $versionService
   * @param LoggerInterface $logger
   * @param FormServerApiAdapterService $formServerApiAdapterService
   * @param $kafkaUrl
   * @param $kafkaEventVersion
   * @param $topics
   */
  public function __construct(
    ScheduleActionService $scheduleActionService,
    RouterInterface $router,
    SerializerInterface $serializer,
    VersionService $versionService,
    LoggerInterface $logger,
    FormServerApiAdapterService $formServerApiAdapterService,
    $kafkaUrl,
    $kafkaEventVersion,
    $topics
  )
  {
    $this->scheduleActionService = $scheduleActionService;
    $this->router = $router;
    $this->serializer = $serializer;
    $this->versionService = $versionService;
    $this->logger = $logger;
    $this->kafkaUrl = $kafkaUrl;
    $this->kafkaEventVersion = $kafkaEventVersion;
    $this->topics = $topics;
    $this->formServerApiAdapterService = $formServerApiAdapterService;
  }


  /**
   * @param $data
   */
  private function produceMessageAsync($params)
  {
    try {
      $this->scheduleActionService->appendAction(
        'ocsdc.kafka_service',
        self::ACTION_PRODUCE_MESSAGE,
        serialize($params)
      );
    } catch (AlreadyScheduledException $e) {
      $this->logger->error('Kafka message is already scheduled', $params);
    }
  }

  /**
   * @param ScheduledAction $action
   * @throws \Exception
   * @throws GuzzleException
   */
  public function executeScheduledAction(ScheduledAction $action)
  {
    $params = unserialize($action->getParams());
    if ($action->getType() == self::ACTION_PRODUCE_MESSAGE) {
      $this->sendMessage($params);
    }
  }

  /**
   * @param $item
   * @throws GuzzleException
   */
  public function produceMessage($item)
  {
    if (empty($this->kafkaUrl)) {
      return;
    }

    // Todo: va creato un registry con i mapper delle singole entità
    if ($item instanceof Pratica) {
      $content = Application::fromEntity(
        $item,
        $this->router->generate('applications_api_list', [], UrlGeneratorInterface::ABSOLUTE_URL) . '/' . $item->getId(),
        true
      );
      $topic = $this->topics['applications'];
    } elseif ($item instanceof Servizio) {
      $content = Service::fromEntity($item, $this->formServerApiAdapterService->getFormServerPublicUrl());
      $topic = $this->topics['services'];
    } else {
      $topic = 'default';
      $content = $item;
    }

    $data = json_decode($this->serializer->serialize($content, 'json'), true);
    $data['event_id'] = Uuid::uuid4()->toString();
    $date = new DateTime();
    $data['event_created_at'] = $date->format(DateTime::W3C);
    $data['event_version'] = $this->kafkaEventVersion;
    $data['app_version'] = $this->versionService->getVersion();
    $data = json_encode($data);

    $params = [
      'topic' => $topic,
      'data'  => $data
    ];

    try {
      $this->sendMessage($params);
    } catch (\Exception $e) {
      $this->produceMessageAsync($params);
    }
  }

  /**
   * @param $data
   * @throws GuzzleException
   */
  private function sendMessage($params)
  {

    $client = new Client();
    $headers = ['Content-Type' => 'application/json'];

    if (substr($this->kafkaUrl, -1) != '/') {
      $this->kafkaUrl .= '/';
    }
    $url = $this->kafkaUrl . $params['topic'];

    $request = new Request(
      'POST',
      $url,
      $headers,
      $params['data']
    );

    /** @var GuzzleResponse $response */
    $response = $client->send($request, ['timeout' => 2]);

    if (!in_array($response->getStatusCode(), [Response::HTTP_OK, Response::HTTP_CREATED])) {
      throw new \Exception("Error sending kafka message: " . $response->getBody());
    }
  }



}
