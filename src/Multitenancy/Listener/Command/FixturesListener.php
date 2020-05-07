<?php

namespace App\Multitenancy\Listener\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Event\ConsoleCommandEvent;

class FixturesListener
{
    const MAIN_GROUP = 'main';

    const TENANT_GROUP = 'tenant';

    /** @var
     * array|string[]
     */
    private $allowedCommands = [
        'doctrine:fixtures:load'
    ];

    private $options = [];

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
            $group = self::TENANT_GROUP;
        } else {
            $group = self::MAIN_GROUP;
        }

        $command->getDefinition()->getOption('group')->setDefault([$group]);
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
