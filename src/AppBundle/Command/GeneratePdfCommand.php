<?php

namespace AppBundle\Command;

use AppBundle\Services\DelayedGiscomAPIAdapterService;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;

class GeneratePdfCommand extends ContainerAwareCommand
{
  /**
   * @var SymfonyStyle
   */
  private $io;

  protected function configure()
  {
    $this
      ->setName('ocsdc:generate-pdf')
      ->setDescription('Generate an application pdf');
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {

    $context = $this->getContainer()->get('router')->getContext();
    $context->setHost($this->getContainer()->getParameter('ocsdc_host'));
    $context->setScheme($this->getContainer()->getParameter('ocsdc_scheme'));

    $helper = $this->getHelper('question');

    $question = new Question('Inserisci id della pratica: ', '');
    $applicationId = $helper->ask($input, $output, $question);

    $entityManager = $this->getContainer()->get('doctrine')->getManager();
    $repository = $entityManager->getRepository('AppBundle:Pratica');

    $application = $repository->find($applicationId);

    if (!$application) {
      $output->writeln('La pratica passata non esiste');
      exit;
    }

    $pdfService  = $this->getContainer()->get('ocsdc.modulo_pdf_builder');
    $pdf = $pdfService->createForPratica($application);
    $application->addModuloCompilato($pdf);

    $entityManager->flush();

    $output->writeln('Pdf generato');

  }
}
