<?php

namespace AppBundle\Command;

use AppBundle\Services\DelayedGiscomAPIAdapterService;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;

class SendPraticaToGiscomCommand extends ContainerAwareCommand
{
  /**
   * @var SymfonyStyle
   */
  private $io;

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

    $repository = $this->getContainer()->get('doctrine')->getRepository('AppBundle:Pratica');

    $application = $repository->find($applicationId);

    if (!$application) {
      $output->writeln('La pratica passata non esiste');
      exit;
    }

    $giscomAdapter  = $this->getContainer()->get('ocsdc.giscom_api.adapter_delayed');
    $giscomAdapter->sendPraticaToGiscom($application);

    /*$giscomeService = $this->getApplication()->getKernel()->getContainer()->get('ocsdc.giscom_api.adapter_direct');
    $response = $giscomeService->sendPraticaToGiscom($application);

    $status = $response->getStatusCode();
    if ($status == 201 || $status == 204) {
      $output->writeln('La pratica è stata inviata correttamente');
    } else {
      $output->writeln("Errore durante l'invio della pratica");
      $output->writeln($response->getBody());
    }*/
  }
}
