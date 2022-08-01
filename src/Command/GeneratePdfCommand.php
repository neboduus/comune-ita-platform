<?php

namespace App\Command;

use App\Services\DelayedGiscomAPIAdapterService;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;

class GeneratePdfCommand extends ContainerAwareCommand
{

  protected function configure()
  {
    $this
      ->setName('ocsdc:generate-pdf')
      ->setDescription('Generate pdf for applications');
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $io = new SymfonyStyle($input, $output);

    $locale = $this->getContainer()->getParameter('locale');
    $this->getContainer()->get('translator')->setLocale($locale);

    $context = $this->getContainer()->get('router')->getContext();
    $context->setHost($this->getContainer()->getParameter('ocsdc_host'));
    $context->setScheme($this->getContainer()->getParameter('ocsdc_scheme'));

    $helper = $this->getHelper('question');

    $question = new Question('Inserisci id della pratica (se più di uno separati da ,): ', '');
    $applicationIdsOption = $helper->ask($input, $output, $question);

    $applicationIds = explode(',', $applicationIdsOption);

    if (empty($applicationIds)) {
      $io->error('Devi inserire un id di pratica o di più pratiche separate da virgola');
      return 1;
    }


    $entityManager = $this->getContainer()->get('doctrine')->getManager();
    $repository = $entityManager->getRepository('App:Pratica');
    $pdfService  = $this->getContainer()->get('ocsdc.modulo_pdf_builder');

    $count = 0;
    foreach ($applicationIds as $applicationId) {
      try {
        $application = $repository->find($applicationId);

        if (!$application) {
          $io->error("La pratica con id: {$applicationId} non esiste");
          continue;
        }

        $pdf = $pdfService->createForPratica($application);
        $application->addModuloCompilato($pdf);
        $io->success("Generato pdf per la pratica: {$applicationId}");
        $count ++;

      } catch (\Exception $e) {

      }
    }
    $entityManager->flush();

    $io->success("Sono stati generati {$count} pdf");

    return 0;

  }
}
