<?php

namespace AppBundle\Command;

use AppBundle\AppBundle;
use AppBundle\Entity\OperatoreUser;

use AppBundle\ScheduledAction\ScheduledActionHandlerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


class SecureUserCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('ocsdc:user-secure:execute')
            ->setDescription('Execute security actions for user class');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $context = $this->getContainer()->get('router')->getContext();
        $context->setHost($this->getContainer()->getParameter('ocsdc_host'));
        $context->setScheme($this->getContainer()->getParameter('ocsdc_scheme'));
        $this->getContainer()->get('logger')->info('Starting a scheduled action');

        $passwordLifeTime = $this->getContainer()->getParameter('PasswordLifeTime');
        $inactiveUserLifeTime = $this->getContainer()->getParameter('InactiveUserLifeTime');

        $em = $this->getContainer()->get('doctrine')->getManager();
        //$repo = $em->getRepository('AppBundle::Operatore');
        //$operatori = $repo->findBy();


        // Operatori da disabilitare
        $operators = $em
            ->createQuery("SELECT * FROM utente WHERE type  = 'operatore' AND enabled = true AND last_change_password  < NOW() - INTERVAL '".$inactiveUserLifeTime." days'")
            ->getResult();

        /** @var OperatoreUser $operator */
        foreach ($operators as $operator) {
            $operator->setEnabled(false);
            $em->persist($operator);
            $em->flush();

        }
        unset($operators);

        // Operatori da modficare la password
        /*$operators = $em
            ->createQuery("SELECT * FROM utente WHERE type  = 'operatore' AND enabled = true last_change_password  < NOW() - INTERVAL '".$passwordLifeTime." days'")
            ->getResult();*/

        /** @var OperatoreUser $operator */
        /*foreach ($operators as $operator) {
            $operator->setLastChangePassword(new \DateTime());
            $operator->setPassword($operator->getPassword().time());
            $em->persist($operator);
            $em->flush();
        }
        unset($operators);*/

    }
}
