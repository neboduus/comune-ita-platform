<?php

namespace AppBundle\Command;

use AppBundle\Entity\ScheduledAction;
use AppBundle\ScheduledAction\ScheduledActionHandlerInterface;
use AppBundle\Services\ScheduleActionService;
use Doctrine\ORM\EntityRepository;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;


class ScheduledActionCommand extends ContainerAwareCommand
{
  protected function configure()
  {
    $this
      ->setName('ocsdc:scheduled_action:execute')
      ->addOption('count', 'c', InputOption::VALUE_REQUIRED, 'Inserisci il numero di azioni da prenotare, default 5')
      ->addOption('hostname', 'f', InputOption::VALUE_OPTIONAL, 'Inserisci hostname per forzare l\'esecuzione da altro host')
      ->addOption('old-reservation-minutes', 'o', InputOption::VALUE_OPTIONAL,
        'Esgue le azioni non ancora eseguite ma già riservate con data di modifica inferiore ad adesso meno i minuti che inserisci, default 60 minuti')
      ->setDescription('Execute all scheduled actions');
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $hostname = gethostname();

    $context = $this->getContainer()->get('router')->getContext();
    $context->setHost($this->getContainer()->getParameter('ocsdc_host'));
    $context->setScheme($this->getContainer()->getParameter('ocsdc_scheme'));
    $this->getContainer()->get('logger')->info('Starting a scheduled action');

    /** @var ScheduleActionService $scheduleActionService */
    $scheduleActionService = $this->getContainer()->get('ocsdc.schedule_action_service');

    $count = (int)$input->getOption('count');
    if (!$count) {
      $count = 5;
    }

    $forceHostname = $input->getOption('hostname');

    $oldReservationMinutes = (int)$input->getOption('old-reservation-minutes');
    if (!$oldReservationMinutes) {
      $oldReservationMinutes = 60;
    }

    if (!$forceHostname) {
      $output->writeln("Reserve $count actions for host $hostname");
      $scheduleActionService->reserveActions($hostname, $count, $oldReservationMinutes);
    } else {
      $hostname = $forceHostname;
      $output->writeln("Force execution for host $hostname");
    }

    $actions = $scheduleActionService->getPendingActions($hostname);
    $count = count($actions);
    $output->writeln("Execute $count actions for host $hostname");

    foreach ($actions as $action) {
      try {
        $service = $this->getContainer()->get($action->getService());
        if ($service instanceof ScheduledActionHandlerInterface) {
          $output->writeln('Execute ' . $action->getType() . ' with params ' . $action->getParams());
          try {
            $service->executeScheduledAction($action);
            $scheduleActionService->markAsDone($action);
          } catch (\Exception $e) {
            $this->getContainer()->get('logger')->error($e->getMessage() . ' on ' . $e->getFile() . '#' . $e->getLine());
          } catch (\ErrorException $e) {
            $this->getContainer()->get('logger')->error($e->getMessage() . ' on ' . $e->getFile() . '#' . $e->getLine());
          }
        } else {
          $this->getContainer()->get('logger')->error($action->getService() . ' must implements ' . ScheduledActionHandlerInterface::class);
          $scheduleActionService->markAsInvalid($action);
        }
      } catch (ServiceNotFoundException $e) {
        $this->getContainer()->get('logger')->error($e->getMessage());
        $scheduleActionService->markAsInvalid($action);
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
      $output->writeln($message);
    }
  }
}
