<?php


namespace AppBundle\Services;


use AppBundle\Dto\Application;
use AppBundle\Dto\Service;
use AppBundle\Entity\Pratica;
use AppBundle\Entity\ScheduledAction;
use AppBundle\Entity\Servizio;
use AppBundle\ScheduledAction\Exception\AlreadyScheduledException;
use AppBundle\ScheduledAction\ScheduledActionHandlerInterface;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use JMS\Serializer\SerializerInterface;
use Psr\Log\LoggerInterface;
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
   * @var EntityManagerInterface
   */
  private $entityManager;
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
  private $kafkaUrl;
  /**
   * @var LoggerInterface
   */
  private $logger;

  /**
   * WebhookService constructor.
   * @param ScheduleActionService $scheduleActionService
   * @param EntityManagerInterface $entityManager
   * @param RouterInterface $router
   * @param SerializerInterface $serializer
   * @param VersionService $versionService
   * @param LoggerInterface $logger
   * @param $kafkaUrl
   */
  public function __construct(ScheduleActionService $scheduleActionService, EntityManagerInterface $entityManager, RouterInterface $router, SerializerInterface $serializer, VersionService $versionService, LoggerInterface $logger, $kafkaUrl)
  {
    $this->scheduleActionService = $scheduleActionService;
    $this->entityManager = $entityManager;
    $this->router = $router;
    $this->serializer = $serializer;
    $this->versionService = $versionService;
    $this->kafkaUrl = $kafkaUrl;
    $this->logger = $logger;
  }


  /**
   * @param $data
   */
  private function produceMessageAsync($data)
  {
    try {
      $params = serialize([
        'data' => $data
      ]);

      $this->scheduleActionService->appendAction(
        'ocsdc.kafka_service',
        self::ACTION_PRODUCE_MESSAGE,
        $params
      );
    } catch (AlreadyScheduledException $e) {
      $this->logger->error('Webhook is already scheduled', $data);
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
      $this->sendMessage($params['data']);
    }
  }

  /**
   * @param $params
   * @param ScheduledAction|null $event
   * @throws GuzzleException
   */
  public function produceMessage($item)
  {

    // Todo: va creato un registry con i mapper delle singole entità
    if ($item instanceof Pratica) {
      $content = Application::fromEntity(
        $item,
        $this->router->generate('applications_api_list', [], UrlGeneratorInterface::ABSOLUTE_URL) . '/' . $item->getId(),
        true
      );
    } elseif ($item instanceof Servizio) {
      $content = Service::fromEntity($item);
    } else {
      $content = $item;
    }

    $data = $this->serializer->serialize($content, 'json');

    try {
      $this->sendMessage($data);
    } catch (\Exception $e) {
      $this->produceMessageAsync($data);
    }
  }

  /**
   * @param $data
   * @throws GuzzleException
   */
  private function sendMessage($data)
  {
    $client = new Client();
    $headers = ['Content-Type' => 'application/json'];

    $request = new Request(
      'POST',
      $this->kafkaUrl,
      $headers,
      $data
    );

    /** @var GuzzleResponse $response */
    $response = $client->send($request, ['timeout' => 2]);

    if (!in_array($response->getStatusCode(), [Response::HTTP_OK, Response::HTTP_CREATED])) {
      throw new \Exception("Error sending kafka message: " . $response->getBody());
    }
  }
}
