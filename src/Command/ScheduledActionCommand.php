<?php

namespace App\Command;

use App\Entity\ScheduledAction;
use App\Exception\DelayedScheduledActionException;
use App\ScheduledAction\ScheduledActionHandlerInterface;
use App\Services\ScheduleActionService;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;


class ScheduledActionCommand extends Command
{
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
    $locale = $this->getContainer()->getParameter('locale');
    $this->getContainer()->get('translator')->setLocale($locale);

    $context = $this->getContainer()->get('router')->getContext();
    $context->setHost($this->getContainer()->getParameter('ocsdc_host'));
    $context->setScheme($this->getContainer()->getParameter('ocsdc_scheme'));

    $logger = $this->getContainer()->get('logger');
    $logger->info('Starting a scheduled action with options: ' . \json_encode($input->getOptions()));

    /** @var ScheduleActionService $scheduleActionService */
    $scheduleActionService = $this->getContainer()->get('ocsdc.schedule_action_service');

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
      $logger->info("Try to reserve $count actions for host $hostname");
      $scheduleActionService->reserveActions($hostname, $count, $oldReservationMinutes, $maxRetry);
    } else {
      $hostname = $forceHostname;
      $logger->info("Force execution for host $hostname");
    }

    $actions = $scheduleActionService->getPendingActions($hostname);

    $count = count($actions);
    $logger->info("Execute $count actions for host $hostname");

    $metrics = $this->getContainer()->get('App\Services\Metrics\ScheduledActionMetrics');
    foreach ($actions as $action) {
      try {
        $service = $this->getContainer()->get($action->getService());
        if ($service instanceof ScheduledActionHandlerInterface) {
          $logger->info('Execute ' . $action->getType() . ' with params ' . $action->getParams());
          try {
            $service->executeScheduledAction($action);
            $scheduleActionService->markAsDone($action);
            $metrics->incScheduledAction($instance, $action->getService(), $action->getType(), 'success');
          } catch (DelayedScheduledActionException $e) {
              $logger->info($e->getMessage());
          } catch (\Throwable $e) {
            $message = $e->getMessage() . ' on ' . $e->getFile() . '#' . $e->getLine();
            $logger->error($message);
            $scheduleActionService->removeHostAndSaveLog($action, $message);
            $metrics->incScheduledAction($instance, $action->getService(), $action->getType(), 'error');
          }
        } else {
          $logger->error($action->getService() . ' must implements ' . ScheduledActionHandlerInterface::class);
          $scheduleActionService->markAsInvalid($action);
          $metrics->incScheduledAction($instance, $action->getService(), $action->getType(), 'invalid');
        }
      } catch (ServiceNotFoundException $e) {
        $logger->error($e->getMessage());
        $scheduleActionService->markAsInvalid($action);
        $metrics->incScheduledAction($instance, $action->getService(), $action->getType(), 'invalid');
      }
    }

    $countByHostname = $scheduleActionService->getStatistic();
    foreach ($countByHostname as $count) {
      $message = 'Pending ' . $count['count'] . ' actions ';
      if (empty($count['hostname'])) {
        $message .= 'not reserved';
      } else {
        $message .= 'reserved by host ' . $count['hostname'];
      }
      $logger->info($message);
    }
  }
}
