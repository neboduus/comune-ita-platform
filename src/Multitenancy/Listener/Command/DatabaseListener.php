<?php

namespace App\Multitenancy\Listener\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Event\ConsoleCommandEvent;

class DatabaseListener
{
    /** @var  array|string[] */
    private $allowedCommands = [
        'doctrine:database:drop',
        'doctrine:database:create',
    ];

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

        if ($input->hasOption('instance') && $input->getOption('instance') !== null) {
            $connection = 'tenant';
            $em = 'default';
        } else {
            $connection = 'main';
            $em = 'main';
        }

        $input->setOption('connection', $connection);
        $input->setOption('em', $em);
        $command->getDefinition()->getOption('connection')->setDefault($connection);
        $command->getDefinition()->getOption('em')->setDefault($em);
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
