<?php


namespace AppBundle\Services;


use AppBundle\Dto\Application;
use AppBundle\Entity\GiscomPratica;
use AppBundle\Entity\Pratica;
use AppBundle\Entity\ScheduledAction;
use AppBundle\Entity\Webhook;
use AppBundle\ScheduledAction\Exception\AlreadyScheduledException;
use AppBundle\ScheduledAction\ScheduledActionHandlerInterface;
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
   * WebhookService constructor.
   * @param ScheduleActionService $scheduleActionService
   * @param EntityManagerInterface $entityManager
   * @param RouterInterface $router
   * @param SerializerInterface $serializer
   */
  public function __construct(ScheduleActionService $scheduleActionService, EntityManagerInterface $entityManager, RouterInterface $router, SerializerInterface $serializer)
  {
    $this->scheduleActionService = $scheduleActionService;
    $this->entityManager = $entityManager;
    $this->router = $router;
    $this->serializer = $serializer;
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
      $this->applicationWebhook($params);
    }
  }

  /**
   * @param $params
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function applicationWebhook($params)
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
      $this->router->generate('applications_api_list', [], UrlGeneratorInterface::ABSOLUTE_URL) . '/' . $pratica->getId()
    );

    $headers = ['Content-Type' => 'application/json'];
    if (!empty($webhook->getHeaders())) {
      $headers = array_merge($headers, json_decode($webhook->getHeaders(), true));
    }

    $json = $this->serializer->serialize($content, 'json');
    $client = new Client();
    $request = new Request(
      $webhook->getMethod(),
      $webhook->getEndpoint(),
      $headers,
      $json
    );

    /** @var Response $response */
    $response = $client->send($request);

    if (!in_array($response->getStatusCode(), [Response::HTTP_OK, Response::HTTP_CREATED, Response::HTTP_ACCEPTED, Response::HTTP_NO_CONTENT])) {
      throw new \Exception("Error sending webhook: " . $response->get());
    }
  }
}
