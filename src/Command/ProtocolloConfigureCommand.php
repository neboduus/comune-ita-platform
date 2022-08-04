<?php

namespace App\Command;

use App\Entity\Ente;
use App\Entity\Servizio;
use App\Protocollo\PiTreProtocolloParameters;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Cache\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;


class ProtocolloConfigureCommand extends Command
{

  private $slugServices = [
    'autorizzazione-paesaggistica-sindaco',
    'comunicazione-inizio-lavori',
    'comunicazione-inizio-lavori-asseverata',
    'comunicazione-opere-libere',
    'dichiarazione-ultimazione-lavori',
    'domanda-permesso-di-costruire',
    'domanda-permesso-di-costruire-in-sanatoria',
    'scia-pratica-edilizia',
    'segnalazione-certificata-di-agibilita',
    's-c-i-a-pratica-edilizia',
  ];

  /**
   * @var EntityManager
   */
  private $entityManager;

  /**
   * @var SymfonyStyle
   */
  private $io;

  /**
   * AdministratorCreateCommand constructor.
   * @param EntityManagerInterface $entityManager
   */
  public function __construct(EntityManagerInterface $entityManager)
  {
    $this->entityManager = $entityManager;
    parent::__construct();
  }

  protected function configure()
  {
    $this
      ->setName('ocsdc:configura-protocollo')
      ->setDescription('Configura i parametri di Protocollo per ciascun Ente');
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $this->io = new SymfonyStyle($input, $output);

    $ente = $this->chooseEnte();
    $this->showEnteCurrentParameters($ente);
    $this->showServicesCurrentParameters($ente);
    $this->configureEnte($ente);
  }

  private function configureEnte(Ente $ente)
  {

    $choice = $this->chooseServizio();

    if ($choice != '*' && $choice != 'Tutti') {
      $servizio = $this->entityManager->getRepository('App\Entity\Servizio')->findOneByName($choice);

      if (!$servizio) {
        $servizio = $this->entityManager->getRepository('App\Entity\Servizio')->findOneBySlug($choice);
      }

      if (!$servizio) {
        throw new InvalidArgumentException("Servizio $servizio non trovato");
      }

      $this->storeData($ente, $servizio);

    } else {
      $this->storeAllServicesData($ente);
    }

    $this->showEnteCurrentParameters($ente);
    $this->showServicesCurrentParameters($ente);

    if ($this->io->confirm('Continuo?')) {
      $this->configureEnte($ente);
    }
  }

  /**
   * @return Ente
   */
  private function chooseEnte()
  {
    $enti = [];
    foreach ($this->getEnti() as $entiEntity) {
      $enti[] = $entiEntity->getName();
    }
    $enteName = $this->io->choice('Seleziona l\'ente da configurare', $enti);
    $ente = $this->entityManager->getRepository('App\Entity\Ente')->findOneByName($enteName);
    if (!$ente) {
      throw new InvalidArgumentException("Ente $enteName non trovato");
    }

    return $ente;
  }

  /**
   * @return Servizio
   */
  private function chooseServizio()
  {
    $servizi = ['*' => 'Tutti'];
    foreach ($this->getServizi() as $servizioEntity) {
      $servizi[$servizioEntity->getSlug()] = $servizioEntity->getName();
    }

    $servizioName = $this->io->choice('Seleziona il servizio da configurare', $servizi);
    return $servizioName;
  }

  private function storeData(Ente $ente, Servizio $servizio)
  {
    $this->io->title("Inserisci parametri per {$servizio->getName()} di {$ente->getName()}");
    $data = [];
    $keys = [
      'recipientIDArray' => 554,
      'recipientTypeIDArray' => 'R',
      'codeNodeClassification' => 1,
      'codeAdm' => 'CCT_CAL',
      'trasmissionIDArray' => 'CCT_CAL',
      'instance' => 'bugliano-test'
    ];
    //$currentParameters = new PiTreProtocolloParameters((array)$ente->getProtocolloParametersPerServizio($servizio));
    $currentParameters = new PiTreProtocolloParameters((array)$servizio->getProtocolloParameters());

    foreach ($keys as $key => $default) {
      // Se è già stato impostato un valore per il parametro corrente lo suggersico altrimenti suggerisco il default
      $suggestion = $currentParameters->has($key) ? $currentParameters->get($key) : $default;
      $data[$key] = $this->io->ask("Inserisci $key", $suggestion);
    }

    $data['protocol_required'] = 1;
    $data['protocol_handler'] = 'pitre';

    //$ente->setProtocolloParametersPerServizio($data, $servizio);
    $servizio->setProtocolRequired(true);
    $servizio->setProtocolHandler('pitre');
    $servizio->setProtocolloParameters($data);
    $this->entityManager->persist($servizio);
    $this->entityManager->flush();
  }

  private function storeAllServicesData(Ente $ente)
  {
    $this->io->title("Inserisci parametri per tutti i servizi");
    $data = [];
    $keys = [
      'recipientIDArray' => 554,
      'recipientTypeIDArray' => 'R',
      'codeNodeClassification' => 1,
      'codeAdm' => 'CCT_CAL',
      'trasmissionIDArray' => 'CCT_CAL',
      'instance' => 'bugliano-test'
    ];

    foreach ($keys as $key => $default) {
      $data[$key] = $this->io->ask("Inserisci $key", $default);
    }
    $data['protocol_required'] = 1;
    $data['protocol_handler'] = 'pitre';

    foreach ($this->getServizi() as $servizio) {
      //$ente->setProtocolloParametersPerServizio($data, $servizio);
      $servizio->setProtocolRequired(true);
      $servizio->setProtocolHandler('pitre');
      $servizio->setProtocolloParameters($data);
      $this->entityManager->persist($servizio);
    }
    $this->entityManager->flush();
  }


  /**
   * @return Servizio[]
   */
  private function getServizi()
  {
    $repo = $this->entityManager->getRepository('App\Entity\Servizio');
    return $repo->findBy([
      'slug' => $this->slugServices
    ]);
  }

  /**
   * @return Ente[]
   */
  private function getEnti()
  {
    $repo = $this->entityManager->getRepository('App\Entity\Ente');

    return $repo->findAll();
  }

  private function showEnteCurrentParameters(Ente $ente)
  {
    $this->io->title("Valori correnti per ente {$ente->getName()}");
    $headers = array_merge(['', 'Servizio'], PiTreProtocolloParameters::getEnteParametersKeys());
    $rows = [];
    foreach ($this->getServizi() as $index => $servizio) {
      $parameters = new PiTreProtocolloParameters((array)$ente->getProtocolloParametersPerServizio($servizio));
      $parameters->remove('protocol_required');
      $parameters->remove('protocol_handler');
      $rows[] = array_merge([$index, $servizio->getName()], $parameters->all());
    }
    $this->io->table($headers, $rows);
  }


  private function showServicesCurrentParameters(Ente $ente)
  {
    $this->io->title("Valori correnti per servizi ({$ente->getName()})");
    $headers = array_merge(['', 'Servizio'], PiTreProtocolloParameters::getEnteParametersKeys());
    $rows = [];
    foreach ($this->getServizi() as $index => $servizio) {
      $parameters = new PiTreProtocolloParameters((array)$servizio->getProtocolloParameters());
      $parameters->remove('protocol_required');
      $parameters->remove('protocol_handler');
      $rows[] = array_merge([$index, $servizio->getName()], $parameters->all());
    }
    $this->io->table($headers, $rows);
  }

}
