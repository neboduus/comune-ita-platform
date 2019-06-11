<?php

namespace AppBundle\Command;

use AppBundle\Entity\ScheduledAction;
use AppBundle\ScheduledAction\ScheduledActionHandlerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


class ScheduledActionCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('ocsdc:scheduled_action:execute')
            ->setDescription('Execute all scheduled actions');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $context = $this->getContainer()->get('router')->getContext();
        $context->setHost($this->getContainer()->getParameter('ocsdc_host'));
        $context->setScheme($this->getContainer()->getParameter('ocsdc_scheme'));
        $this->getContainer()->get('logger')->info('Starting a scheduled action');
        $scheduleActionService = $this->getContainer()->get('ocsdc.schedule_action_service');

        $actions = $scheduleActionService->getActions();
        foreach ($actions as $action) {
            $service = $this->getContainer()->get($action->getService());
            if ($service instanceof ScheduledActionHandlerInterface) {
                $output->writeln('Execute ' . $action->getType() . ' with params ' . $action->getParams());
                try {
                    $service->executeScheduledAction($action);
                    $scheduleActionService->markAsDone($action);
                } catch (\Exception $e) {
                    $this->getContainer()->get('logger')->error($e->getMessage());
                } catch (\ErrorException $e) {                    
                    $this->getContainer()->get('logger')->error($e->getMessage() . ' on ' . $e->getFile() . '#' . $e->getLine());
                }                            
            } else {
                $this->getContainer()->get('logger')->error($action->getService() . ' must implements ' . ScheduledActionHandlerInterface::class);
                $scheduleActionService->markAsInvalid($action);
            }
        }
        $scheduleActionService->done();
    }
}
