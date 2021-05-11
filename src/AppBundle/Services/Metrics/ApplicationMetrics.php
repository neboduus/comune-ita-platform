<?php


namespace AppBundle\Services\Metrics;


use AppBundle\Entity\Pratica;
use Artprima\PrometheusMetricsBundle\Metrics\MetricsGeneratorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Prometheus\CollectorRegistry;
use Prometheus\Exception\MetricNotFoundException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\PostResponseEvent;

class ApplicationMetrics  implements MetricsGeneratorInterface
{
  /**
   * @var string
   */
  private $namespace;

  /**
   * @var CollectorRegistry
   */
  private $collectionRegistry;
  /**
   * @var EntityManagerInterface
   */
  private $entityManager;
  /**
   * @var LoggerInterface
   */
  private $logger;

  /**
   * ApplicationMetrics constructor.
   * @param EntityManagerInterface $entityManager
   * @param LoggerInterface $logger
   */
  public function __construct(EntityManagerInterface $entityManager, LoggerInterface $logger)
  {
    $this->entityManager = $entityManager;
    $this->logger = $logger;
  }

  /**
   * @param string $namespace
   * @param CollectorRegistry $collectionRegistry
   */
  public function init(string $namespace, CollectorRegistry $collectionRegistry)
  {
    $this->namespace = $namespace;
    $this->collectionRegistry = $collectionRegistry;
  }

  public function collectRequest(GetResponseEvent $event)
  {
    $this->setApplications();
  }

  public function collectResponse(PostResponseEvent $event)
  {
    // TODO: Implement collectResponse() method.
  }

  private function setApplications(): void
  {

    $praticaRepository = $this->entityManager->getRepository(Pratica::class);
    $metrics = $praticaRepository->getMetrics();

    try {
      foreach ($metrics as $m) {
        $counter = $this->collectionRegistry->getOrRegisterCounter(
          $this->namespace,
          'applications',
          'applications A summary of the application count',
          ['tenant', 'service', 'status', 'category']
        );

        $counter->incBy($m['count'], [$m['ente'], $m['servizio'], $m['status'], $m['categoria']]);

      }
    } catch (\Exception $e) {
      $this->logger->error($e->getMessage());
    }

  }
}
