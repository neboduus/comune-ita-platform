<?php

namespace App\Command;

use App\Entity\Pratica;
use App\Entity\PraticaRepository;
use App\Services\PraticaStatusService;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class HotFixStatusConsistencyCommand extends Command
{

  /** @var EntityManagerInterface */
  private $entityManager;

  /**
   * @var PraticaStatusService
   */
  private $praticaStatusService;

  public function __construct(EntityManagerInterface $entityManager, PraticaStatusService $praticaStatusService)
  {
    $this->entityManager = $entityManager;
    parent::__construct();
    $this->praticaStatusService = $praticaStatusService;
  }

  protected function configure()
  {

    $this
      ->setName('ocsdc:hotfix-status_consistency')
      ->setDescription('Imposta lo stato della pratica da acquistita 2000 a protocollata 3000 se è presente il numero di protocollo')
      ->addOption('servizio', null, InputOption::VALUE_REQUIRED, 'Id servizio')
      ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Dry run');
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    try {

      $dryRun = $input->getOption('dry-run');
      $serviceId = $input->getOption('servizio');

      /** @var PraticaRepository $repo */
      $repo = $this->entityManager->getRepository('App\Entity\Pratica');

      $qb = $repo->createQueryBuilder('p')
        ->where('p.servizio = :servizio')
        ->andWhere('p.status = :status')
        ->andWhere('p.numeroProtocollo is not null')
        ->setParameter('servizio', $serviceId)
        ->setParameter('status', Pratica::STATUS_SUBMITTED);

      /** @var Pratica[] $pratiche */
      $pratiche = $qb->getQuery()->getResult();

      foreach ($pratiche as $index => $pratica) {
        $index++;
        $output->writeln($index . ' ' . $pratica->getId());
        if (!$dryRun) {
          $this->praticaStatusService->setNewStatus($pratica, Pratica::STATUS_REGISTERED);
        }
      }
      return 0;

    } catch (\Exception $e) {
      $output->writeln('Error: ' . $e->getMessage());
      return 1;
    }
  }

}
