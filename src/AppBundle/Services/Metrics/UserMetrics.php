<?php


namespace AppBundle\Services\Metrics;

use Artprima\PrometheusMetricsBundle\Metrics\MetricsGeneratorInterface;
use Prometheus\CollectorRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\PostResponseEvent;
use function Couchbase\defaultDecoder;

class UserMetrics  implements MetricsGeneratorInterface
{

  private const PREFIX = 'users';

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
      $this->registerLoginSuccessCounter();
      $this->registerLoginFailureCounter();
    } catch (\Exception $e) {
      $this->logger->error($e->getMessage());
    }
  }

  public function collectResponse(PostResponseEvent $event)
  {
    try {
      $this->registerLoginSuccessCounter();
      $this->registerLoginFailureCounter();
    } catch (\Exception $e) {
      $this->logger->error($e->getMessage());
    }
  }

  public function incLoginSuccess($tenant, $authType, $authMethod, $authLevel): void
  {
    try {
      $counter = $this->registerLoginSuccessCounter();
      $counter->inc([$tenant, $authType, $authMethod, $authLevel]);
    } catch (\Exception $e) {
      $this->logger->error($e->getMessage());
    }
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

  public function incLoginFailure($tenant, $authType, $authException): void
  {
    try {
      $counter = $this->registerLoginFailureCounter();
      $counter->inc([$tenant, $authType, $authException]);
    } catch (\Exception $e) {
      $this->logger->error($e->getMessage());
    }
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
}
