<?php

namespace App\Command;

use App\Entity\Calendar;
use App\Entity\OpeningHour;
use App\Entity\Servizio;
use App\Model\FlowStep;
use App\Services\BackOfficeCollection;
use App\Services\InstanceService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use App\Entity\Categoria;
use App\Entity\Erogatore;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Class BuiltInCreateCommand
 */
class BuiltInCreateCommand extends Command
{

  /** @var EntityManagerInterface */
  private EntityManagerInterface $entityManager;

  /** @var InstanceService */
  private InstanceService $instanceService;

  /** @var BackOfficeCollection */
  private BackOfficeCollection $backOfficeCollection;

  /**
   * AdministratorCreateCommand constructor.
   * @param EntityManagerInterface $entityManager
   * @param InstanceService $instanceService
   * @param BackOfficeCollection $backOfficeCollection
   */
  public function __construct(EntityManagerInterface $entityManager, InstanceService $instanceService, BackOfficeCollection $backOfficeCollection)
  {
    $this->entityManager = $entityManager;
    $this->instanceService = $instanceService;
    $this->backOfficeCollection = $backOfficeCollection;
    parent::__construct();
  }

  protected function configure()
  {
    $this
      ->setName('ocsdc:built-in-services-import')
      ->addOption('file', 'f', InputOption::VALUE_REQUIRED, 'Load data from file')
      ->setDescription(
        'Crea un servizio built-in. Usare -f path/al/file.json'
      );
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $symfonyStyle = new SymfonyStyle($input, $output);

    $file = $input->getOption('file');
    $symfonyStyle->writeln('Loading data from file ' . $file);
    if (!file_exists($file)) {
      $symfonyStyle->error('File ' . $file . " not found");
      return 1;
    }

    $parsed = json_decode(file_get_contents($file), true);
    $name = $parsed['name'];
    $slug = $parsed['slug'];
    $topic = $parsed['topic'];
    $description = $parsed['description'];
    $shortDescription = $parsed['short_description'];
    $identifier = $parsed['identifier'];
    $howTo = $parsed['howto'];
    $howToDo = $parsed['how_to_do'];
    $whatYouNeed = $parsed['what_you_need'];
    $whatYouGet = $parsed['what_you_get'];
    $costs = $parsed['costs'];
    $who = $parsed['who'];
    $special_cases = $parsed['special_cases'];
    $more_info = $parsed['more_info'];
    $constraints = $parsed['constraints'];
    $times_and_deadlines = $parsed['times_and_deadlines'];
    $conditions = $parsed['conditions'];
    $final_indications = $parsed['final_indications'];
    $accessLevel = $parsed['access_level'];
    $workflow = $parsed['workflow'];
    $handler = "";
    $integrationsData = $parsed['integrations'];
    $calendarData = $parsed['calendar'];
    $status = $parsed['status'];
    $flow = 'ocsdc.form.flow.formio';

    $serviziRepo = $this->entityManager->getRepository('App\Entity\Servizio');
    $categoryRepo = $this->entityManager->getRepository('App\Entity\Categoria');

    $servizio = $serviziRepo->findOneBy(['identifier' => $identifier]);
    $ente = $this->instanceService->getCurrentInstance();

    if (!$servizio instanceof Servizio) {
      $symfonyStyle->writeln('Service ' . $identifier . ' not found, create built-in service');
      $servizio = new Servizio();
    } else if (!$servizio->isBuiltIn()){
      $symfonyStyle->error('Service ' . $identifier . ' already exists but is not built-in, no action will be performed');
      return 1;
    } else {
      // Update built-in service with json data
      $symfonyStyle->writeln('Service ' . $identifier . ' already exists, update service data');
    }

    // Update service data
    $servizio
      ->setName($name)
      ->setSlug($slug)
      ->setDescription($description)
      ->setShortDescription($shortDescription)
      ->setIdentifier($identifier)
      ->setHowto($howTo)
      ->setHowToDo($howToDo)
      ->setWhatYouNeed($whatYouNeed)
      ->setWhatYouGet($whatYouGet)
      ->setCosts($costs)
      ->setWho($who)
      ->setSpecialCases($special_cases)
      ->setMoreInfo($more_info)
      ->setConstraints($constraints)
      ->setTimesAndDeadlines($times_and_deadlines)
      ->setConditions($conditions)
      ->setFinalIndications($final_indications)
      ->setAccessLevel($accessLevel)
      ->setStatus($status)
      ->setHandler($handler)
      ->setWorkflow($workflow)
      ->setPraticaFCQN('\App\Entity\BuiltIn')
      ->setPraticaFlowServiceName($flow)
      ->setEnte($ente);

    // Integrazioni
    $integrations = [];
    foreach ($integrationsData as $activationPoint => $backOfficeIdentifier) {
      // Verifico correttezza identifier backoffice
      $backoffice = $this->backOfficeCollection->getBackOfficeByIdentifier($backOfficeIdentifier);
      if (!$backoffice) {
        $symfonyStyle->error('Backoffice ' . $backOfficeIdentifier . ' does not exists');
        return 1;
      }
      // Verifico correttezza del punto di attivazione
      if (!$backoffice->isAllowedActivationPoint($activationPoint)) {
        $symfonyStyle->error('Backoffice ' . $backOfficeIdentifier . ' does not support activation point ' . $activationPoint);
      }
      $integrations[$activationPoint] = get_class($backoffice);
    }
    $servizio->setIntegrations($integrations);

    // Categorie
    $area = $categoryRepo->findOneBySlug($topic);
    if ($area instanceof Categoria) {
      $servizio->setTopics($area);
    } else {
      $area = $categoryRepo->findOneBy([], ['name' => 'ASC']);
      if ($area instanceof Categoria) {
        $servizio->setTopics($area);
      }
    }

    // Calendario: creo un calendario solo se non è presente un calendario con il nome passato come parametro
    // Se esiste NON ne aggiorno i dati come viene fatto per il servizio
    if ($calendarData) {
      $title = $calendarData['title'];
      $type = $calendarData['type'] ?? Calendar::TYPE_TIME_FIXED;
      $openingHoursData = $calendarData['opening_hours'];

      $adminRepo = $this->entityManager->getRepository('App\Entity\AdminUser');

      $calendar = $this->entityManager->createQueryBuilder()
        ->select('calendar')
        ->from(Calendar::class, 'calendar')
        ->where('lower(calendar.title) = lower(:title)')
        ->setParameter('title', $title)
        ->getQuery()->getResult();


      if (!$calendar) {
        $admin = $adminRepo->findOneBy([], ['createdAt' => 'ASC']);

        $calendar = new Calendar();

        $calendar
          ->setTitle($title)
          ->setLocation($ente->getName())
          ->setType($type)
          ->setOwner($admin);

        $this->entityManager->persist($calendar);

        foreach ($openingHoursData as $openingHourData) {
          $beginHour = $openingHourData['begin_hour'] ?? Calendar::MIN_DATE;
          $endHour = $openingHourData['end_hour'] ?? Calendar::MAX_DATE;
          $daysOfWeek = $openingHourData['days_of_week'] ?? [1, 2, 3, 4, 5];

          $openingHour = new OpeningHour();

          $openingHour
            ->setCalendar($calendar)
            ->setStartDate(new \DateTime('now'))
            ->setEndDate(new \DateTime(date('Y') + 1 . '-12-31'))
            ->setBeginHour(\DateTime::createFromFormat('H:i', $beginHour))
            ->setEndHour(\DateTime::createFromFormat('H:i', $endHour))
            ->setDaysOfWeek($daysOfWeek);

          $this->entityManager->persist($openingHour);
        }
      } else {
        $symfonyStyle->writeln('Calendar ' . $title . ' already exists');
      }
    }

    $flowStep = new FlowStep();
    $flowStep
      ->setIdentifier($identifier)
      ->setType('built-in');
    $servizio->setFlowSteps([$flowStep]);

    $this->entityManager->persist($servizio);

    $erogatore = new Erogatore();
    $erogatore->setName('Erogatore di ' . $servizio->getName() . ' per ' . $ente->getName());
    $erogatore->addEnte($ente);
    $this->entityManager->persist($erogatore);
    $servizio->activateForErogatore($erogatore);
    $this->entityManager->flush();

    $symfonyStyle->success('Built-in service ' . $identifier . ' successfully imported');

    return 0;
  }
}
