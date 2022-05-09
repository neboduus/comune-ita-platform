<?php

namespace AppBundle\Command;


use AppBundle\Entity\Pratica;
use Ramsey\Uuid\Uuid;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;


class ChangeApplicationStatusCommand extends ContainerAwareCommand
{

  protected function configure()
  {
    $this
      ->setName('ocsdc:application:change-status')
      ->addOption('id', null, InputOption::VALUE_REQUIRED, 'Id of the application')
      ->addOption('status', null, InputOption::VALUE_REQUIRED, 'New status for de application')
      ->addOption('force', null, InputOption::VALUE_NONE, 'Force change status and don\'t check if change is allowed')
      ->setDescription('Change status for given application');
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {

    $this->symfonyStyle = new SymfonyStyle($input, $output);

    $context = $this->getContainer()->get('router')->getContext();
    $context->setHost($this->getContainer()->getParameter('ocsdc_host'));
    $context->setScheme($this->getContainer()->getParameter('ocsdc_scheme'));

    $id = $input->getOption('id');
    $status = $input->getOption('status');
    $force = $input->getOption('force');

    if (!Uuid::isValid($id)) {
      $this->symfonyStyle->error('Option id must be an uuid');
      return 1;
    }

    $em = $this->getContainer()->get('doctrine')->getManager();

    $application = $em->getRepository('AppBundle:Pratica')->find($id);
    if (!$application instanceof Pratica) {
      $this->symfonyStyle->error('Application with id:' . $id . ' not found.');
      return 1;
    }

    $allowedStatuses = Pratica::getStatuses();

    //dump(!is_int($status));
    /*if (!is_int($status)) {
      $status = Pratica::getStatusCodeByName($status);
    }*/

    if (!isset($allowedStatuses[$status])) {
      $this->symfonyStyle->error('Submitted status doesn\'t exists');
      return 1;
    }

    $praticaStatusService = $this->getContainer()->get('ocsdc.pratica_status_service');
    try {
      $praticaStatusService->setNewStatus($application, $status);
      $this->symfonyStyle->success('Application status changed succesfully' );
      return 0;
    } catch (\Exception $e) {
      $this->symfonyStyle->error($e->getMessage());
      return 1;
    }
  }
}
