<?php

namespace App\Command;

use App\DataFixtures\ORM\LoadData;
use App\Utils\StringUtils;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PropertyAccess\PropertyAccess;

class PopulateServiceShortDescriptionCommand extends Command
{

  /** @var EntityManagerInterface */
  private $entityManager;

  public function __construct(EntityManagerInterface $entityManager)
  {
    $this->entityManager = $entityManager;
    parent::__construct();
  }

  protected function configure()
  {
    $this
      ->setName('ocsdc:services:populate-short-description')
      ->setDescription('Prende la descrizione di un servizio, la taglia, pulisce e la inserisce come descrizione breve')
      ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Dry run');
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {

    $symfonyStyle = new SymfonyStyle($input, $output);
    try {
      $dryRun = $input->getOption('dry-run');

      $services = $this->entityManager->getRepository('App\Entity\Servizio')->findBy(
        [
          'shortDescription' => null,
        ]
      );

      $servicesToUpdate = 0;
      foreach ($services as $service) {
        if (!empty($service->getDescription())) {
          $servicesToUpdate++;
          if (!$dryRun) {
            $service->setShortDescription(StringUtils::shortenDescription($service->getDescription()));
            $this->entityManager->persist($service);
          }
        }
      }
      $this->entityManager->flush();

      if (!empty($servicesToUpdate)) {
        if (!$dryRun) {
          $symfonyStyle->success('Ci sono da aggiornare '.$servicesToUpdate.' servizi:');
        } else {
          $symfonyStyle->note('Verranno aggiornati '.$servicesToUpdate.' servizi:');
        }
      } else {
        $symfonyStyle->note('Non sono presenti servizi da aggiornare.');
      }

    } catch (\Exception $e) {
      $symfonyStyle->error('Error: '.$e->getMessage());
      return 1;
    }

    return 0;
  }
}
