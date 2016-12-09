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
        $em = $this->getContainer()->get('doctrine')->getManager();
        /** @var EntityRepository $repository */
        $repository = $em->getRepository('AppBundle:ScheduledAction');

        /** @var ScheduledAction[] $actions */
        $actions = $repository->findBy([], ['createdAt' => 'ASC']);
        foreach($actions as $action){
            $service = $this->getContainer()->get($action->getService());
            if ($service instanceof ScheduledActionHandlerInterface){
                $output->writeln('Execute ' . $action->getType() . ' with params ' . $action->getParams());
                try {
                    $service->executeScheduledAction($action);
                    $em->remove($action);
                }catch(\Exception $e){
                    $this->getContainer()->get('logger')->error($e->getMessage());
                }
            }
        }
        $em->flush();
    }
}
