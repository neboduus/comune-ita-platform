<?php

namespace AppBundle\Command;

use AppBundle\Entity\Pratica;
use AppBundle\Services\DelayedGiscomAPIAdapterService;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;

class RemoveTooManyPdfCommand extends ContainerAwareCommand
{

  protected function configure()
  {
    $this
      ->setName('ocsdc:remove-pdf')
      ->setDescription('Removes all generated pdf beyond the first one');
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

    $count = 0;
    foreach ($applicationIds as $applicationId) {
      try {
        /** @var Pratica $application */
        $application = $repository->find($applicationId);

        if (!$application) {
          $io->error("La pratica con id: {$applicationId} non esiste");
          continue;
        }

        $progressBar = new ProgressBar($output, $application->getModuliCompilati()->count());
        $count = $removed = 0;
        foreach ($application->getModuliCompilati() as $item) {
          $progressBar->advance();
          ++$count;
          $modId = $item->getId();

          if ($count === 1) {
            continue;
          }
          $application->removeModuloCompilato($item);
          $entityManager->remove($item);
          $entityManager->persist($application);
          //$io->success("Rimosso modulo: {$modId}");
          ++$removed;
        }
      } catch (\Exception $e) {
        $io->success("Errore rimuovendo il modulo: {$modId} - " . $e->getMessage());
      }
      $progressBar->finish();
    }
    $entityManager->flush();
    $io->success("Sono stati rimossi {$count} pdf");

    return 0;

  }
}
