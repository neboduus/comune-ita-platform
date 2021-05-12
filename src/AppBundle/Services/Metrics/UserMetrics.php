<?php


namespace AppBundle\Services\Metrics;

use Artprima\PrometheusMetricsBundle\Metrics\MetricsGeneratorInterface;
use Prometheus\CollectorRegistry;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\PostResponseEvent;

class UserMetrics  implements MetricsGeneratorInterface
{

  private const PREFIX = 'users';

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
    //$this->registerLoginSuccessCounter();
  }

  public function collectResponse(PostResponseEvent $event)
  {
    //$this->registerLoginSuccessCounter();
  }

  private function registerLoginSuccessCounter()
  {
    return $this->collectionRegistry->getOrRegisterCounter(
      $this->namespace,
      'login_success',
      'A summary of success login',
      [self::PREFIX . '_tenant', self::PREFIX . '_auth_type', self::PREFIX . '_auth_method', self::PREFIX . '_auth_level']
    );
  }

  public function incLoginSuccess($tenant, $authType, $authMethod, $authLevel): void
  {
    $counter = $this->registerLoginSuccessCounter();
    $counter->inc([$tenant, $authType, $authMethod, $authLevel]);
  }

  private function registerLoginFailureCounter()
  {
    return $this->collectionRegistry->getOrRegisterCounter(
      $this->namespace,
      'login_failure',
      'A summary of failure login',
      [self::PREFIX . '_tenant', self::PREFIX . '_auth_type', self::PREFIX . '_auth_exception']
    );
  }

  public function incLoginFailure($tenant, $authType, $authException): void
  {
    $counter = $this->registerLoginFailureCounter();
    $counter->inc([$tenant, $authType, $authException]);
  }
}
