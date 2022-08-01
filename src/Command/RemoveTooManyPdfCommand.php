<?php

namespace App\Command;

use App\Entity\Pratica;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class RemoveTooManyPdfCommand extends Command
{

  private $router;

  private $scheme;

  private $host;
  /**
   * @var EntityManagerInterface
   */
  private $entityManager;
  /**
   * @var string
   */
  private $locale;
  /**
   * @var TranslatorInterface
   */
  private $translator;


  /**
   * @param EntityManagerInterface $entityManager
   * @param RouterInterface $router
   * @param TranslatorInterface $translator
   * @param string $locale
   * @param string $scheme
   * @param string $host
   */
  public function __construct(EntityManagerInterface $entityManager, RouterInterface $router, TranslatorInterface $translator, string $locale, string $scheme, string $host)
  {
    $this->entityManager = $entityManager;
    $this->router = $router;
    $this->translator = $translator;
    $this->locale = $locale;
    $this->scheme = $scheme;
    $this->host = $host;
    parent::__construct();
  }

  protected function configure()
  {
    $this
      ->setName('ocsdc:remove-pdf')
      ->setDescription('Removes all generated pdf beyond the first one');
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $io = new SymfonyStyle($input, $output);

    $this->translator->setLocale($this->locale);
    $context = $this->router->getContext();
    $context->setHost($this->host);
    $context->setScheme($this->scheme);

    $helper = $this->getHelper('question');

    $question = new Question('Inserisci id della pratica (se più di uno separati da ,): ', '');
    $applicationIdsOption = $helper->ask($input, $output, $question);

    $applicationIds = explode(',', $applicationIdsOption);

    if (empty($applicationIds)) {
      $io->error('Devi inserire un id di pratica o di più pratiche separate da virgola');
      return 1;
    }

    $repository = $this->entityManager->getRepository('App\Entity\Pratica');

    $count = 0;
    foreach ($applicationIds as $applicationId) {
      $progressBar = new ProgressBar($output, $application->getModuliCompilati()->count());
      try {
        /** @var Pratica $application */
        $application = $repository->find($applicationId);

        if (!$application) {
          $io->error("La pratica con id: {$applicationId} non esiste");
          continue;
        }

        $count = $removed = 0;
        $modId = '';
        foreach ($application->getModuliCompilati() as $item) {
          $progressBar->advance();
          ++$count;
          $modId = $item->getId();

          if ($count === 1) {
            continue;
          }
          $application->removeModuloCompilato($item);
          $this->entityManager->remove($item);
          $this->entityManager->persist($application);
          //$io->success("Rimosso modulo: {$modId}");
          ++$removed;
        }
      } catch (\Exception $e) {
        $io->success("Errore rimuovendo il modulo: {$modId} - " . $e->getMessage());
      }
      $progressBar->finish();
    }
    $this->entityManager->flush();
    $io->success("Sono stati rimossi {$count} pdf");

    return 0;

  }
}
