<?php


namespace AppBundle\Services\Metrics;

use Artprima\PrometheusMetricsBundle\Metrics\MetricsGeneratorInterface;
use Prometheus\CollectorRegistry;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\PostResponseEvent;

class ScheduledActionMetrics  implements MetricsGeneratorInterface
{

  private const PREFIX = 'scheduled_actions';

  /**
   * @var string
   */
  private $namespace;

  /**
   * @var CollectorRegistry
   */
  private $collectionRegistry;

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
    $this->registerCounter();
  }

  public function collectResponse(PostResponseEvent $event)
  {
    $this->registerCounter();
  }

  private function registerCounter()
  {
    return $this->collectionRegistry->getOrRegisterCounter(
      $this->namespace,
      'scheduled_actions',
      'A summary of the scheduled actions',
      [self::PREFIX . self::PREFIX . '_tenant', self::PREFIX . '_service', self::PREFIX . '_type', self::PREFIX . '_status']
    );
  }

  public function incScheduledAction($tenant, $service, $type, $status): void
  {
    $counter = $this->registerCounter();
    $counter->inc([$tenant, $service, $type, $status]);
  }
}
