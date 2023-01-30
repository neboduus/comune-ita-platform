<?php

namespace App\Command;

use App\Entity\Servizio;
use App\Model\FlowStep;
use App\Services\FormServerApiAdapterService;
use App\Services\InstanceService;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use App\Entity\Categoria;
use App\Entity\Erogatore;

/**
 * Class BuiltInCreateCommand
 */
class BuiltInCreateCommand extends Command
{

  /** @var EntityManagerInterface */
  private EntityManagerInterface $entityManager;

  /** @var InstanceService */
  private InstanceService $instanceService;

  /** @var FormServerApiAdapterService */
  private FormServerApiAdapterService $formServer;

  /**
   * AdministratorCreateCommand constructor.
   * @param EntityManagerInterface $entityManager
   * @param InstanceService $instanceService
   * @param FormServerApiAdapterService $formServer
   * @param string $scheme
   * @param string $host
   * @param string $prefix
   */
  public function __construct(EntityManagerInterface $entityManager, InstanceService $instanceService, FormServerApiAdapterService $formServer)
  {
    $this->entityManager = $entityManager;
    $this->instanceService = $instanceService;
    $this->formServer = $formServer;
    parent::__construct();
  }

  protected function configure()
  {
    $this
      ->setName('ocsdc:crea-servizio-built-in')
      ->addOption('file', 'f', InputOption::VALUE_REQUIRED, 'Load data from file')
      ->setDescription(
        'Crea un servizio built-in. Usare -f path/al/file.yml'
      );
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $file = $input->getOption('file');
    $output->writeln("<info>Loading data from file $file</info>");
    if (!file_exists($file)) {
      $output->writeln("<error>$file file not found</error>");
      die(1);
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
    $conditions = $parsed['conditions'];
    $accessLevel = $parsed['access_level'];
    $workflow = $parsed['workflow'];
    $handler = "";
    $status = $parsed['status'];
    $flow = 'ocsdc.form.flow.formio';
    $formSchema = $parsed['form'];

    $serviziRepo = $this->entityManager->getRepository('App\Entity\Servizio');
    $categoryRepo = $this->entityManager->getRepository('App\Entity\Categoria');

    $servizio = $serviziRepo->findOneBy(['identifier' => $identifier]);
    $ente = $this->instanceService->getCurrentInstance();

    if (!$servizio instanceof Servizio) {

      $servizio = new Servizio();
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
        ->setConditions($conditions)
        ->setAccessLevel($accessLevel)
        ->setStatus($status)
        ->setHandler($handler)
        ->setWorkflow($workflow)
        ->setPraticaFCQN('\App\Entity\BuiltIn')
        ->setPraticaFlowServiceName($flow)
        ->setEnte($ente);

      $area = $categoryRepo->findOneBySlug($topic);
      if ($area instanceof Categoria) {
        $servizio->setTopics($area);
      } else {
        $area = $categoryRepo->findOneBy([], ['name' => 'ASC']);
        if ($area instanceof Categoria) {
          $servizio->setTopics($area);
        }
      }

      // Ricerco il form per path che coincide con l'identifier del servizio, se non esiste creo un form
      // dato lo schema definito nel json del servizio altrimenti aggangio al servizio il form eistente
      // In questo modo tutti i servizio built-in con lo stesso identifier condividono il medesimo form
      // nel formserver
      $response = $this->formServer->getFormBySlug($identifier);
      if ($response['status'] != 'success') {
        $output->writeln("<info>Creazione form {$identifier}</info>");

        try {
          $response = $this->formServer->createFormFromSchema($formSchema);
          $formId = $response['form_id'];
        } catch (GuzzleException $e) {
          $output->writeln("<error>Si è verificato un errore durante la creazione del form per il servizio built-in {$name}</error>");
          die(1);
        }
      } else {
        $formId = $response['form']['_id'];
      }

      $flowStep = new FlowStep();
      $flowStep
        ->setIdentifier($formId)
        ->setType('formio')
        ->addParameter('formio_id', $formId);
      $servizio->setFlowSteps([$flowStep]);
      // Backup
      $additionalData = $servizio->getAdditionalData();
      $additionalData['formio_id'] = $formId;
      $servizio->setAdditionalData($additionalData);

      $this->entityManager->persist($servizio);

      $erogatore = new Erogatore();
      $erogatore->setName('Erogatore di ' . $servizio->getName() . ' per ' . $ente->getName());
      $erogatore->addEnte($ente);
      $this->entityManager->persist($erogatore);
      $servizio->activateForErogatore($erogatore);
      $this->entityManager->flush();


      $output->writeln("<info>Servizio creato correttamente</info>");

    } else {
      $output->writeln("<error>Servizio già presente, non verrà eseguita nessuan azione</error>");
    }
    return 0;
  }
}
