<?php


namespace AppBundle\Services;


use AppBundle\Dto\Application;
use AppBundle\Entity\GiscomPratica;
use AppBundle\Entity\Pratica;
use AppBundle\Entity\ScheduledAction;
use AppBundle\Entity\Webhook;
use AppBundle\ScheduledAction\Exception\AlreadyScheduledException;
use AppBundle\ScheduledAction\ScheduledActionHandlerInterface;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use JMS\Serializer\SerializerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;


class WebhookService implements ScheduledActionHandlerInterface
{

  const SCHEDULED_APPLICATION_WEBHOOK = 'application_webhook';

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

  /**
   * WebhookService constructor.
   * @param ScheduleActionService $scheduleActionService
   * @param EntityManagerInterface $entityManager
   * @param RouterInterface $router
   * @param SerializerInterface $serializer
   * @param VersionService $versionService
   */
  public function __construct(ScheduleActionService $scheduleActionService, EntityManagerInterface $entityManager, RouterInterface $router, SerializerInterface $serializer, VersionService $versionService)
  {
    $this->scheduleActionService = $scheduleActionService;
    $this->entityManager = $entityManager;
    $this->router = $router;
    $this->serializer = $serializer;
    $this->versionService = $versionService;
  }


  /**
   * @param Pratica|GiscomPratica $pratica
   * @param Webhook $webhook
   * @throws AlreadyScheduledException
   */
  public function createApplicationWebhookAsync(Pratica $pratica, Webhook $webhook)
  {
    $params = serialize([
      'pratica' => $pratica->getId(),
      'webhook' => $webhook->getId()
    ]);

    $this->scheduleActionService->appendAction(
      'ocsdc.webhook_service',
      self::SCHEDULED_APPLICATION_WEBHOOK,
      $params
    );
  }

  /**
   * @param ScheduledAction $action
   * @throws \Exception
   */
  public function executeScheduledAction(ScheduledAction $action)
  {
    $params = unserialize($action->getParams());
    if ($action->getType() == self::SCHEDULED_APPLICATION_WEBHOOK) {
      $this->applicationWebhook($params, $action);
    }
  }

  /**
   * @param $params
   * @param ScheduledAction|null $event
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function applicationWebhook($params, ScheduledAction $event = null)
  {

    /** @var Pratica $pratica */
    $pratica = $this->entityManager->getRepository('AppBundle:Pratica')->find($params['pratica']);
    if (!$pratica instanceof Pratica) {
      throw new \Exception('Not found application with id: ' . $params['pratica']);
    }

    /** @var Webhook $webhook */
    $webhook = $this->entityManager->getRepository('AppBundle:Webhook')->find($params['webhook']);
    if (!$webhook instanceof Webhook) {
      throw new \Exception('Not found webhook with id: ' . $params['pratica']);
    }

    $content = Application::fromEntity(
      $pratica,
      $this->router->generate('applications_api_list', [], UrlGeneratorInterface::ABSOLUTE_URL) . '/' . $pratica->getId(),
      true,
      $webhook->getVersion()
    );

    $headers = ['Content-Type' => 'application/json'];
    if (!empty($webhook->getHeaders())) {
      $headers = array_merge($headers, json_decode($webhook->getHeaders(), true));
    }

    $data = json_decode($this->serializer->serialize($content, 'json'), true);

    if ($event) {
      $data['event_id'] = $event->getId();
      $data['event_created_at'] = $event->getCreatedAt()->format(DateTime::W3C);
    }
    $data['event_version'] = $webhook->getVersion();
    $data['app_version'] = $this->versionService->getVersion();

    $client = new Client();
    $request = new Request(
      $webhook->getMethod(),
      $webhook->getEndpoint(),
      $headers,
      json_encode($data)
    );

    /** @var Response $response */
    $response = $client->send($request);

    if (!in_array($response->getStatusCode(), [Response::HTTP_OK, Response::HTTP_CREATED, Response::HTTP_ACCEPTED, Response::HTTP_NO_CONTENT])) {
      throw new \Exception("Error sending webhook: " . $response->getContent());
    }
  }
}
