<?php


namespace AppBundle\Services\Metrics;


use AppBundle\Entity\Pratica;
use Artprima\PrometheusMetricsBundle\Metrics\MetricsGeneratorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Prometheus\CollectorRegistry;
use Prometheus\Exception\MetricNotFoundException;
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
   * ApplicationMetrics constructor.
   * @param EntityManagerInterface $entityManager
   */
  public function __construct(EntityManagerInterface $entityManager)
  {
    $this->entityManager = $entityManager;
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
        $name = str_replace('-', '_', 'applications' . $m['servizio']) . '_' . $m['status'];
        /*$gauge = $this->collectionRegistry->registerGauge(
          $this->namespace,
          $name,
          'applications A summary of the application count',
          ['tenant', 'service', 'status', 'category']
        );
        $gauge->set($m['count'], [$m['servizio'], $m['status'], $m['ente'], $m['categoria']]);*/

        $counter = $this->collectionRegistry->getOrRegisterCounter(
          $this->namespace,
          'applications',
          'applications A summary of the application count',
          ['tenant', 'service', 'status', 'category']
        );

        //$counter->inc(['all']);
        $counter->incBy($m['count'], [$m['servizio'], $m['status'], $m['ente'], $m['categoria']]);

      }
    } catch (\Exception $e) {
      dump($e);
    }

  }
}
