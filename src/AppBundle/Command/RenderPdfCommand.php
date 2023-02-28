<?php

namespace AppBundle\Command;

use AppBundle\Entity\ModuloCompilato;
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

    $count = 0;
    foreach ($applicationIds as $applicationId) {
      try {
        $application = $repository->find($applicationId);

        if (!$application) {
          $io->error("La pratica con id: {$applicationId} non esiste");
          continue;
        }


        $fileName = uniqid() . '.pdf';
        $moduloCompilato = new ModuloCompilato();
        $moduloCompilato->setOwner($application->getUser());
        $moduloCompilato->setFilename($fileName);
        $moduloCompilato->setMimeType($pdfService::MIME_TYPE);
        $moduloCompilato->setOriginalFilename($fileName);
        $moduloCompilato->setDescription($fileName);

        $destinationDirectory = $pdfService->getDestinationDirectoryFromContext($moduloCompilato);
        $filePath = $destinationDirectory . DIRECTORY_SEPARATOR . $fileName;
        $content = $pdfService->renderForPratica($application);
        $pdfService->filesystem->getFilesystem()->write($filePath, $content);
        $moduloCompilato->setFile(new File($filePath, false));

        $entityManager->persist($moduloCompilato);

        $application->addModuloCompilato($moduloCompilato);
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
