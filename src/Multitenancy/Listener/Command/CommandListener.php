<?php

namespace App\Multitenancy\Listener\Command;

use App\Multitenancy\Doctrine\DBAL\TenantConnection;
use App\Multitenancy\Entity\Main\Tenant;
use App\Multitenancy\TenantMatcher;
use App\Services\InstanceService;
use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Input\InputOption;

class CommandListener
{
    /**
     * @var  array|string[]
     */
    private $allowedCommands;

    /**
     * @var TenantConnection
     */
    private $tenantConnection;

    /**
     * @var TenantMatcher
     */
    private $matcher;

    /**
     * @var InstanceService
     */
    private $instanceService;

    public function __construct(
        TenantConnection $tenantConnection,
        TenantMatcher $matcher,
        InstanceService $instanceService,
        $allowedCommands = []
    ) {
        $this->tenantConnection = $tenantConnection;
        $this->matcher = $matcher;
        $this->instanceService = $instanceService;
        $this->allowedCommands = $allowedCommands;
    }

    /**
     * @param ConsoleCommandEvent $event
     * @throws \Exception
     */
    public function onConsoleCommand(ConsoleCommandEvent $event)
    {
        $command = $event->getCommand();
        $input = $event->getInput();

        if (!$this->isProperCommand($command)) {
            return;
        }

        $command->getDefinition()->addOption(
            new InputOption('instance', 'i', InputOption::VALUE_OPTIONAL, 'Tenant database name', null)
        );

        if (!$command->getDefinition()->hasOption('em')) {
            $command->getDefinition()->addOption(
                new InputOption('em', null, InputOption::VALUE_OPTIONAL, 'The entity manager to use for this command')
            );
        }

        $input->bind($command->getDefinition());

        if (is_null($input->getOption('instance'))) {
            $input->setOption('em', 'main');
            $command->getDefinition()->getOption('em')->setDefault('main');
        } else {
            $tenantIdentifier = $input->getOption('instance');
            $input->setOption('em', 'default');
            $command->getDefinition()->getOption('em')->setDefault('default');

            /** @var Tenant $tenant */
            $tenant = $this->matcher->matchFromIdentifier($tenantIdentifier);

            if ($tenant === null) {
                throw new Exception(sprintf('Instance identified as %s does not exists', $tenantIdentifier));
            }

            $this->tenantConnection->changeParams(
                $tenant->getDbHost(),
                $tenant->getDbPort(),
                $tenant->getDbName(),
                $tenant->getDbUser(),
                $tenant->getDbPassword()
            );
            $this->instanceService->setTenant($tenant);
        }
    }

    /**
     * @param Command $command
     * @return bool
     */
    private function isProperCommand(Command $command)
    {
        return in_array($command->getName(), $this->allowedCommands);
    }
}
