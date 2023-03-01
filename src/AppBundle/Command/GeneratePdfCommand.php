<?php

namespace AppBundle\Command;

use AppBundle\Entity\ModuloCompilato;
use AppBundle\Entity\Pratica;
use AppBundle\Services\DelayedGiscomAPIAdapterService;
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
    $repository = $entityManager->getRepository('AppBundle:Pratica');
    $pdfService  = $this->getContainer()->get('ocsdc.modulo_pdf_builder');
    $fileSystem = $this->getContainer()->get('ocsdc.filesystem');

    $count = 0;
    foreach ($applicationIds as $applicationId) {
      try {
        /** @var Pratica $application */
        $application = $repository->find($applicationId);

        if (!$application) {
          $io->error("La pratica con id: {$applicationId} non esiste");
          continue;
        }

        $pdf = $pdfService->renderForPratica($application);

        /** @var ModuloCompilato $modulo */
        $modulo = $application->getModuliCompilati()->first();

        $path = str_replace('uploads/', '', $modulo->getFile()->getPathname());
        $result = $fileSystem->getFilesystem()->put($path, $pdf);

        if ($result) {
          $io->success("Generato pdf per la pratica: {$applicationId} al seguente path:" . $modulo->getFile()->getPathname());
          $count ++;
        } else {
          $io->error("Errore salvando il pdf per la pratica: {$applicationId} al seguente path:" . $modulo->getFile()->getPathname());
        }

      } catch (\Exception $e) {
        $io->error($e->getMessage());
      }
    }

    $io->success("Sono stati generati {$count} pdf");

    return 0;

  }
}
