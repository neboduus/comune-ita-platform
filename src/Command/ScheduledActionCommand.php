<?php

namespace App\Command;

use App\Services\SchedulableActionRegistry;
use App\Services\ScheduleActionService;
use Doctrine\ORM\EntityManagerInterface;
use App\Services\InstanceService;
use App\ScheduledAction\ScheduledActionHandlerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command as BaseCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;

class ScheduledActionCommand extends BaseCommand
{
    private $em;

    private $instanceService;

    private $logger;

    private $scheduleActionService;

    private $router;

    private $schedulableActionRegistry;

    private $scheme;

    private $host;

    public function __construct(
        EntityManagerInterface $manager,
        InstanceService $instanceService,
        LoggerInterface $logger,
        ScheduleActionService $scheduleActionService,
        RouterInterface $router,
        SchedulableActionRegistry $schedulableActionRegistry,
        string $scheme,
        string $host
    ) {
        $this->em = $manager;
        $this->instanceService = $instanceService;
        $this->logger = $logger;
        $this->scheduleActionService = $scheduleActionService;
        $this->router = $router;
        $this->schedulableActionRegistry = $schedulableActionRegistry;
        $this->scheme = $scheme;
        $this->host = $host;

        parent::__construct();
    }

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

        $context = $this->router ->getContext();
        $context->setHost($this->host);
        $context->setScheme($this->scheme);

        $this->logger->info('Starting a scheduled action');

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
            $this->scheduleActionService->reserveActions($hostname, $count, $oldReservationMinutes);
        } else {
            $hostname = $forceHostname;
            $output->writeln("Force execution for host $hostname");
        }

        $actions = $this->scheduleActionService->getPendingActions($hostname);
        $count = count($actions);
        $output->writeln("Execute $count actions for host $hostname");

        foreach ($actions as $action) {
            try {
                $service = $this->schedulableActionRegistry->getByName($action->getService());
                if ($service instanceof ScheduledActionHandlerInterface) {
                    $output->writeln('Execute ' . $action->getType() . ' with params ' . $action->getParams());
                    try {
                        $service->executeScheduledAction($action);
                        $this->scheduleActionService->markAsDone($action);
                    } catch (\Exception $e) {
                        $this->logger->error($e->getMessage() . ' on ' . $e->getFile() . '#' . $e->getLine());
                    } catch (\Error $e) {
                        $this->logger->error($e->getMessage() . ' on ' . $e->getFile() . '#' . $e->getLine());
                    }
                } else {
                    $this->logger->error($action->getService() . ' must implements ' . ScheduledActionHandlerInterface::class);
                    $this->scheduleActionService->markAsInvalid($action);
                }
            } catch (ServiceNotFoundException $e) {
                $this->logger->error($e->getMessage());
                $this->scheduleActionService->markAsInvalid($action);
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
            $output->writeln($message);
        }

        return 0;
    }
}
