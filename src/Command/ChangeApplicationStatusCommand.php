<?php

namespace App\Command;


use App\Entity\Pratica;
use Doctrine\ORM\EntityManagerInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;


class ChangeApplicationStatusCommand extends Command
{

  /** @var EntityManagerInterface */
  private $entityManager;

  /** @var RouterInterface */
  private $router;


  /**
   * AdministratorCreateCommand constructor.
   * @param EntityManagerInterface $entityManager
   */
  public function __construct(EntityManagerInterface $entityManager, RouterInterface $router)
  {
    $this->entityManager = $entityManager;
    parent::__construct();
    $this->router = $router;
  }

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

    $symfonyStyle = new SymfonyStyle($input, $output);

    $context = $this->router->getContext();
    $context->setHost($this->getContainer()->getParameter('ocsdc_host'));
    $context->setScheme($this->getContainer()->getParameter('ocsdc_scheme'));

    $id = $input->getOption('id');
    $status = $input->getOption('status');
    $force = $input->getOption('force');

    if (!Uuid::isValid($id)) {
      $symfonyStyle->error('Option id must be an uuid');
      return 1;
    }

    $application = $this->entityManager->getRepository('App\Entity\Pratica')->find($id);
    if (!$application instanceof Pratica) {
      $symfonyStyle->error('Application with id:' . $id . ' not found.');
      return 1;
    }

    $allowedStatuses = Pratica::getStatuses();

    if (!isset($allowedStatuses[$status])) {
      $symfonyStyle->error('Submitted status doesn\'t exists');
      return 1;
    }

    $praticaStatusService = $this->getContainer()->get('ocsdc.pratica_status_service');
    try {
      $praticaStatusService->setNewStatus($application, $status);
      $symfonyStyle->success('Application status changed succesfully' );
      return 0;
    } catch (\Exception $e) {
      $symfonyStyle->error($e->getMessage());
      return 1;
    }
  }
}
