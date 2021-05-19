<?php


namespace AppBundle\Services\Metrics;

use Artprima\PrometheusMetricsBundle\Metrics\MetricsGeneratorInterface;
use Prometheus\CollectorRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\PostResponseEvent;

class ScheduledActionMetrics implements MetricsGeneratorInterface
{

  private const PREFIX = 'scheduled_actions';

  /**
   * @var LoggerInterface
   */
  private $logger;

  /**
   * @var string
   */
  private $namespace;

  /**
   * @var CollectorRegistry
   */
  private $collectionRegistry;

  /**
   * ApplicationMetrics constructor.
   * @param LoggerInterface $logger
   */
  public function __construct(LoggerInterface $logger)
  {
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
    try {
      $this->registerCounter();
    } catch (\Exception $e) {
      $this->logger->error($e->getMessage());
    }
  }

  public function collectResponse(PostResponseEvent $event)
  {
    try {
      $this->registerCounter();
    } catch (\Exception $e) {
      $this->logger->error($e->getMessage());
    }
  }

  private function registerCounter()
  {
    return $this->collectionRegistry->getOrRegisterCounter(
      $this->namespace,
      'scheduled_actions',
      'A summary of the scheduled actions',
      [self::PREFIX.'_tenant', self::PREFIX.'_service', self::PREFIX.'_type', self::PREFIX.'_status']
    );
  }

  public function incScheduledAction($tenant, $service, $type, $status): void
  {
    try {
      $counter = $this->registerCounter();
      $counter->inc([$tenant, $service, $type, $status]);
    } catch (\Exception $e) {
      $this->logger->error($e->getMessage());
    }
  }
}
