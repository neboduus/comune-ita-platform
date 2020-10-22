<?php

namespace App\Command;

use App\Entity\Ente;
use App\Entity\Servizio;
use App\Protocollo\PiTreProtocolloParameters;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Cache\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;


class ProtocolloConfigureCommand extends ContainerAwareCommand
{
  /**
   * @var EntityManager
   */
  private $em;

  /**
   * @var SymfonyStyle
   */
  private $io;

  protected function configure()
  {
    $this
      ->setName('ocsdc:configura-protocollo')
      ->setDescription('Configura i parametri di Protocollo per ciascun Ente');
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $this->em = $this->getContainer()->get('doctrine')->getManager();
    $this->io = new SymfonyStyle($input, $output);

    $ente = $this->chooseEnte();
    $this->showEnteCurrentParameters($ente);
    $this->configureEnte($ente);
  }

  private function configureEnte(Ente $ente)
  {
    $servizio = $this->chooseServizio();

    if ($servizio != '*' && $servizio != 'Tutti') {
      if ($servizio) {
        $servizio = $this->em->getRepository('App:Servizio')->findOneByName($servizio);
      }

      if (!$servizio) {
        $servizio = $this->em->getRepository('App:Servizio')->findOneBySlug($servizio);
      }

      if (!$servizio) {
        throw new InvalidArgumentException("Servizio $servizio non trovato");
      }

      $this->storeData($ente, $servizio);

    } else {
      $this->storeAllServicesData($ente);
    }

    $this->showEnteCurrentParameters($ente);

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
    $ente = $this->em->getRepository('App:Ente')->findOneByName($enteName);
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
      $servizi[(string)$servizioEntity->getSlug()] = $servizioEntity->getName();
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
      'instance' => 'treville-test'
    ];
    $currentParameters = new PiTreProtocolloParameters((array)$ente->getProtocolloParametersPerServizio($servizio));

    foreach ($keys as $key => $default) {
      // Se è già stato impostato un valore per il parametro corrente lo suggersico altrimenti suggerisco il default
      $suggestion = $currentParameters->has($key) ? $currentParameters->get($key) : $default;
      $data[$key] = $this->io->ask("Inserisci $key", $suggestion);
    }

    $ente->setProtocolloParametersPerServizio($data, $servizio);
    $this->em->flush($ente);
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
      'instance' => 'treville-test'
    ];

    foreach ($keys as $key => $default) {
      $data[$key] = $this->io->ask("Inserisci $key", $default);
    }

    foreach ($this->getServizi() as $servizio) {
      $ente->setProtocolloParametersPerServizio($data, $servizio);
    }
    $this->em->flush($ente);
  }


  /**
   * @return Servizio[]
   */
  private function getServizi()
  {
    $repo = $this->em->getRepository('App:Servizio');

    return $repo->findAll();
  }

  /**
   * @return Ente[]
   */
  private function getEnti()
  {
    $repo = $this->em->getRepository('App:Ente');

    return $repo->findAll();
  }

  private function showEnteCurrentParameters(Ente $ente)
  {
    $this->io->title("Valori correnti per ente {$ente->getName()}");
    $headers = array_merge(['', 'Servizio'], PiTreProtocolloParameters::getEnteParametersKeys());
    $rows = [];
    foreach ($this->getServizi() as $index => $servizio) {
      $parameters = new PiTreProtocolloParameters((array)$ente->getProtocolloParametersPerServizio($servizio));
      $rows[] = array_merge([$index, $servizio->getName()], $parameters->all());
    }
    $this->io->table($headers, $rows);
  }
}