<?php

namespace App\Command;

use App\Entity\Servizio;
use App\Services\Satisfy\SatisfyService;
use Doctrine\ORM\EntityManagerInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Class BuiltInCreateCommand
 */
class ConfigureSatisfyCommand extends Command
{

  private EntityManagerInterface $entityManager;
  private SatisfyService $satisfyService;

  /**
   * AdministratorCreateCommand constructor.
   * @param EntityManagerInterface $entityManager
   * @param SatisfyService $satisfyService
   */
  public function __construct(EntityManagerInterface $entityManager, SatisfyService $satisfyService)
  {
    $this->entityManager = $entityManager;
    $this->satisfyService = $satisfyService;
    parent::__construct();

  }

  protected function configure()
  {
    $this
      ->setName('ocsdc:services:configure-satisfy')
      ->addOption(
        'service',
        null,
        InputOption::VALUE_REQUIRED,
        'Specify uuid of the service to create the corresponding entrypoint, type "all" to create entrypoints for all services those who do not have it'
      )
      ->addOption('delete', null, InputOption::VALUE_NONE, 'Delete entrypoints');
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {

    $serviceOption = $input->getOption('service');
    $delete = $input->getOption('delete');

    $symfonyStyle = new SymfonyStyle($input, $output);
    $servicesRepo = $this->entityManager->getRepository('App\Entity\Servizio');

    $services = [];
    if ($serviceOption === 'all') {
      $services = $servicesRepo->findAll();
    } elseif (Uuid::isValid($serviceOption)) {
      $service = $servicesRepo->find($serviceOption);
      if (!$service instanceof Servizio) {
        $symfonyStyle->error('Service not found');

        return 1;
      }
      $services [] = $service;
    }

    foreach ($services as $s) {
      if ($delete) {
        try {
          $symfonyStyle->note('Delete satisfy entrypoint for service '.$s->getName());
          $this->satisfyService->deleteEntryPoint($s);
        } catch (\Exception $e) {
          $symfonyStyle->error('Error on delete satisfy entrypoint for service '.$s->getName().' - '.$e->getMessage());
        }
      } else {
        try {
          $symfonyStyle->note('Configure satisfy entrypoint for service '.$s->getName());
          $this->satisfyService->syncEntryPoint($s);
        } catch (\Exception $e) {
          $symfonyStyle->error('Error on configure satisfy entrypoint for service '.$s->getName().' - '.$e->getMessage());
        }
      }
    }

    return 0;
  }
}
