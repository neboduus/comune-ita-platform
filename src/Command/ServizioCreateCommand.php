<?php

namespace App\Command;

use App\Entity\Servizio;
use App\Services\InstanceService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Cache\Exception\InvalidArgumentException;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;
use App\Entity\Categoria;
use App\Entity\Ente;
use App\Entity\Erogatore;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

/**
 * Class ServizioCreateCommand
 */
class ServizioCreateCommand extends Command
{

  /** @var EntityManagerInterface */
  private $entityManager;

  /** @var InstanceService */
  private $instanceService;

  /**
   * AdministratorCreateCommand constructor.
   * @param EntityManagerInterface $entityManager
   * @param InstanceService $instanceService
   */
  public function __construct(EntityManagerInterface $entityManager, InstanceService $instanceService)
  {
    $this->entityManager = $entityManager;
    $this->instanceService = $instanceService;
    parent::__construct();
  }

  protected function configure()
  {
    $this
      ->setName('ocsdc:crea-servizio')
      ->addOption('file', 'f', InputOption::VALUE_OPTIONAL, 'Load data from file instead of interactively')
      ->setDescription(
        'Crea un record nella tabella servizi. Usare -f path/al/file.yml per la versione non interattiva'
      );
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $name = "";
    $handler = "";
    $area = "";
    $description = "";
    $istruzioni = "";
    $fcqn = "";
    $flow = "";
    $flowOperator = "";
    $paymentParameters = [];
    $paymentRequired = false;
    $status = 1;
    $additionalData = [
      'urlModuloPrincipale' => '',
      'urlModuliAggiuntivi' => []
    ];

    $shouldLoadFromFile = $input->getOption('file');
    if ($shouldLoadFromFile) {
      $output->writeln("<info>Loading data from file $shouldLoadFromFile</info>");
      if (!file_exists($shouldLoadFromFile)) {
        $output->writeln("<error>$shouldLoadFromFile file not found</error>");
        die(1);
      }
      $parsed = json_decode(file_get_contents($shouldLoadFromFile), true);
      $name = $parsed['name'];
      $handler = $parsed['handler'];
      $area = $parsed['area'];
      $description = $parsed['description'];
      $istruzioni = $parsed['istruzioni'];
      $fcqn = $parsed['fcqn'];
      $flow = $parsed['flow'];
      $flowOperator = $parsed['flowOperator'];
      $paymentParameters = $parsed['paymentParameters'];
      $paymentRequired = $parsed['paymentRequired'];
      $status = $parsed['status'];
      $additionalData['urlModuloPrincipale'] = $parsed['url_modulo_principale'] ?? '';
      $additionalData['urlModuliAggiuntivi'] = $parsed['url_moduli_aggiuntivi'] ?? [];
    } else {
      $output->writeln("<info>Loading data interactively</info>");
      $helper = $this->getHelper('question');

      // Name
      $question = new Question('Inserisci il nome del Servizio: ', '');
      $question->setValidator(function ($answer) {
        if (!is_string($answer) || empty($answer)) {
          throw new \RuntimeException(
            'Inserimento obbligatoorio'
          );
        }
        return $answer;
      });
      $name = $helper->ask($input, $output, $question);

      // Handler
      $question = new Question('Inserisci l\'handler del Servizio: ', '');
      $question->setValidator(function ($answer) {
        if (!is_string($answer)) {
          throw new \RuntimeException(
            'Inserisci una stringa'
          );
        }
        return $answer;
      });
      $handler = $helper->ask($input, $output, $question);

      // Area
      $question = new Question('Inserisci Area del Servizio: ', '');
      $question->setValidator(function ($answer) {
        if (empty($answer)) {
          throw new \RuntimeException(
            'Inserimento obbligatoorio'
          );
        }
        if (!is_numeric($answer)) {
          throw new \RuntimeException(
            'Sono ammessi solo numeri interi'
          );
        }
        return $answer;
      });
      $area = $helper->ask($input, $output, $question);

      // Description
      $question = new Question('Inserisci la descrizione del Servizio: ', '');
      $description = $helper->ask($input, $output, $question);

      // Istruzioni
      $question = new Question('Inserisci le istruzioni del Servizio: ', '');
      $istruzioni = $helper->ask($input, $output, $question);

      // fcqn
      $question = new Question('Inserisci fcqn del Servizio: ', '');
      $fcqn = $helper->ask($input, $output, $question);

      // flow
      $question = new Question('Inserisci flow del Servizio: ', '');
      $flow = $helper->ask($input, $output, $question);

      // flow operatore
      $question = new Question('Inserisci flow operatore del Servizio: ', '');
      $flowOperator = $helper->ask($input, $output, $question);
    }


    $serviziRepo = $this->entityManager->getRepository('App\Entity\Servizio');
    $categoryRepo = $this->entityManager->getRepository('App\Entity\Categoria');

    $servizio = $serviziRepo->findOneByName($name);
    $ente = $this->instanceService->getCurrentInstance();
    if (!$servizio instanceof Servizio) {

      $servizio = new Servizio();
      $servizio
        ->setName($name)
        ->setHandler($handler)
        ->setDescription($description)
        ->setHowto($istruzioni)
        ->setPraticaFCQN($fcqn)
        ->setPraticaFlowServiceName($flow)
        ->setPraticaFlowOperatoreServiceName($flowOperator)
        ->setPaymentParameters($paymentParameters)
        ->setPaymentRequired($paymentRequired)
        ->setAdditionalData($additionalData)
        ->setStatus($status)
        ->setEnte($ente);

      $area = $categoryRepo->findOneBySlug('catasto-e-urbanistica');
      if ($area instanceof Categoria) {
        $servizio->setTopics($area);
      } else {
        $area = $categoryRepo->findOneBy([], ['name' => 'ASC']);
        if ($area instanceof Categoria) {
          $servizio->setTopics($area);
        }
      }

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
