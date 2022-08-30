<?php

namespace App\Command;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

class SendPraticaToGiscomCommand extends Command
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
      ->setName('ocsdc:giscom:send-pratica')
      ->setDescription('Invia una pratica a giscom');
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {

    $helper = $this->getHelper('question');

    $question = new Question('Inserisci id della pratica: ', '');
    $applicationId = $helper->ask($input, $output, $question);

    $repository = $this->entityManager->getRepository('App\Entity\Pratica');

    $application = $repository->find($applicationId);

    if (!$application) {
      $output->writeln('La pratica passata non esiste');
      return 1;
    }

    $giscomService = $this->getApplication()->getKernel()->getContainer()->get('ocsdc.giscom_api.adapter_direct');
    $response = $giscomService->sendPraticaToGiscom($application);

    $status = $response->getStatusCode();
    if ($status == 201 || $status == 204) {
      $output->writeln('La pratica è stata inviata correttamente');

      return 0;
    } else {
      return 1;
    }
  }
}
