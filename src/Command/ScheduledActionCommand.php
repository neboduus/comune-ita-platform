<?php

namespace App\Command;

use App\Entity\ScheduledAction;
use App\Exception\DelayedScheduledActionException;
use App\ScheduledAction\ScheduledActionHandlerInterface;
use App\Services\InstanceService;
// use App\Services\Metrics\ScheduledActionMetrics;
use App\Services\SchedulableActionRegistry;
use App\Services\ScheduleActionService;
//use Artprima\PrometheusMetricsBundle\Metrics\MetricsCollectorInterface;
use Doctrine\ORM\EntityRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;


class ScheduledActionCommand extends Command
{

  private $logger;

  private $scheduleActionService;

  private $router;

  private $schedulableActionRegistry;

  private $scheme;

  private $host;
  /**
   * @var string
   */
  private $locale;
  /**
   * @var TranslatorInterface
   */
  private $translator;

  public function __construct(
    LoggerInterface $logger,
    ScheduleActionService $scheduleActionService,
    RouterInterface $router,
    SchedulableActionRegistry $schedulableActionRegistry,
    TranslatorInterface $translator,
    string $locale,
    string $scheme,
    string $host
  ) {
    $this->logger = $logger;
    $this->scheduleActionService = $scheduleActionService;
    $this->router = $router;
    $this->schedulableActionRegistry = $schedulableActionRegistry;
    $this->scheme = $scheme;
    $this->host = $host;

    parent::__construct();
    $this->locale = $locale;
    $this->translator = $translator;
    // TODO: riabilitare le metriche nella classe appena soddisfatte le altre dipendenze di Prometheus
    // $this->metrics = $metrics;
  }

  protected function configure()
  {
    $this
      ->setName('ocsdc:scheduled_action:execute')
      ->addOption('count', 'c', InputOption::VALUE_REQUIRED, 'Inserisci il numero di azioni da prenotare, default 5')
      ->addOption('hostname', 'f', InputOption::VALUE_OPTIONAL, 'Inserisci hostname per forzare l\'esecuzione da altro host')
      ->addOption('old-reservation-minutes', 'o', InputOption::VALUE_OPTIONAL,
        'Esegue le azioni non ancora eseguite ma già riservate con data di modifica inferiore ad adesso meno i minuti che inserisci, default 10 minuti')
      ->addOption('max-retry', 'm', InputOption::VALUE_OPTIONAL, 'Numero massimo di ripetizioni per un\'azione, default 10')
      ->setDescription('Execute all scheduled actions');
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $hostname = gethostname();

    $instance = $input->getOption('instance');
    if (empty($instance)) {
      $instance = 'default';
    }

    // Default locale
    $this->translator->setLocale($this->locale);
    $context = $this->router->getContext();
    $context->setHost($this->host);
    $context->setScheme($this->scheme);


    $this->logger->info('Starting a scheduled action with options: ' . \json_encode($input->getOptions()));

    $count = (int)$input->getOption('count');
    if (!$count) {
      $count = 5;
    }

    $forceHostname = $input->getOption('hostname');

    $oldReservationMinutes = (int)$input->getOption('old-reservation-minutes');
    if (!$oldReservationMinutes) {
      $oldReservationMinutes = 10;
    }

    $maxRetry = (int)$input->getOption('max-retry');
    if (!$maxRetry) {
      $maxRetry = 10;
    }

    if (!$forceHostname) {
      $this->logger->info("Try to reserve $count actions for host $hostname");
      $this->scheduleActionService->reserveActions($hostname, $count, $oldReservationMinutes, $maxRetry);
    } else {
      $hostname = $forceHostname;
      $this->logger->info("Force execution for host $hostname");
    }

    $actions = $this->scheduleActionService->getPendingActions($hostname);

    $count = count($actions);
    $this->logger->info("Execute $count actions for host $hostname");

    foreach ($actions as $action) {
      try {
        $service = $this->schedulableActionRegistry->getByName($action->getService());
        if ($service instanceof ScheduledActionHandlerInterface) {
          $this->logger->info('Execute ' . $action->getType() . ' with params ' . $action->getParams());
          try {
            $service->executeScheduledAction($action);
            $this->scheduleActionService->markAsDone($action);
            // $this->metrics->incScheduledAction($instance, $action->getService(), $action->getType(), 'success');
          } catch (DelayedScheduledActionException $e) {
              $this->logger->info($e->getMessage());
          } catch (\Throwable $e) {
            $message = $e->getMessage() . ' on ' . $e->getFile() . '#' . $e->getLine();
            $this->logger->error($message);
            $this->scheduleActionService->removeHostAndSaveLog($action, $message);
            // $this->metrics->incScheduledAction($instance, $action->getService(), $action->getType(), 'error');
          }
        } else {
          $this->logger->error($action->getService() . ' must implements ' . ScheduledActionHandlerInterface::class);
          $this->scheduleActionService->markAsInvalid($action);
          // $this->metrics->incScheduledAction($instance, $action->getService(), $action->getType(), 'invalid');
        }
      } catch (ServiceNotFoundException $e) {
        $this->logger->error($e->getMessage());
        $this->scheduleActionService->markAsInvalid($action);
        // $this->metrics->incScheduledAction($instance, $action->getService(), $action->getType(), 'invalid');
      }
    }

    $countByHostname = $this->scheduleActionService->getStatistic();
    foreach ($countByHostname as $count) {
      $message = 'Pending ' . $count['count'] . ' actions ';
      if (empty($count['hostname'])) {
        $message .= 'not reserved';
      } else {
        $message .= 'reserved by host ' . $count['hostname'];
      }
      $this->logger->info($message);
    }

    return 0;
  }
}
