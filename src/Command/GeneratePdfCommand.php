<?php

namespace App\Command;

use App\Services\DelayedGiscomAPIAdapterService;
use App\Services\InstanceService;
use App\Services\ModuloPdfBuilderService;
use App\Services\SchedulableActionRegistry;
use App\Services\ScheduleActionService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class GeneratePdfCommand extends Command
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
   * @var ModuloPdfBuilderService
   */
  private $moduloPdfBuilderService;

  /**
   * @param EntityManagerInterface $entityManager
   * @param RouterInterface $router
   * @param string $scheme
   * @param string $host
   */
  public function __construct(EntityManagerInterface $entityManager, RouterInterface $router, TranslatorInterface $translator, ModuloPdfBuilderService $moduloPdfBuilderService, string $locale, string $scheme, string $host)
  {
    $this->entityManager = $entityManager;
    $this->router = $router;
    $this->translator = $translator;
    $this->locale = $locale;
    $this->scheme = $scheme;
    $this->host = $host;
    parent::__construct();

    $this->moduloPdfBuilderService = $moduloPdfBuilderService;
  }

  protected function configure()
  {
    $this
      ->setName('ocsdc:generate-pdf')
      ->setDescription('Generate pdf for applications');
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
      try {
        $application = $repository->find($applicationId);

        if (!$application) {
          $io->error("La pratica con id: {$applicationId} non esiste");
          continue;
        }

        $pdf = $this->moduloPdfBuilderService->createForPratica($application);
        $application->addModuloCompilato($pdf);
        $io->success("Generato pdf per la pratica: {$applicationId}");
        $count ++;

      } catch (\Exception $e) {

      }
    }
    $this->entityManager->flush();

    $io->success("Sono stati generati {$count} pdf");

    return 0;

  }
}
