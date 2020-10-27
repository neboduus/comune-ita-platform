<?php

namespace App\Command;

use App\Entity\OperatoreUser;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use InvalidArgumentException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;

/**
 * Class OperatoreAbilitaServizio
 */
class OperatoreAbilitaServizioCommand extends Command
{
  /** @var EntityManagerInterface */
  private $entityManager;

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
      ->setName('ocsdc:abilita-operatore-per-servizio')
      ->setDescription('Crea un record nella tabella utente di tipo operatore');
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $helper = $this->getHelper('question');

    $question = new Question('Inserisci lo username ');
    $username = $helper->ask($input, $output, $question);
    $operatoriRepo = $this->entityManager->getRepository('App:OperatoreUser');
    /** @var OperatoreUser $user */
    $user = $operatoriRepo->findOneByUsername($username);
    if (!$user) {
      throw new InvalidArgumentException('utente non trovato');
    }

    $serviziAbilitati = $user->getServiziAbilitati();

    $erogatori = $user->getEnte()->getErogatori()->toArray();
    $servizi = [];
    foreach ($erogatori as $erogatore) {
      $serviziErogati = $erogatore->getServizi()->toArray();
      $servizi = array_merge($servizi, $serviziErogati);
    }

    $serviziNames = ['*' => '(tutti)'];
    foreach ($servizi as $servizio) {
      if (!$serviziAbilitati->contains($servizio->getId())) {
        $serviziNames[(string)$servizio->getId()] = $servizio->getName();
      }
    }

    $question = new ChoiceQuestion('Seleziona il servizio da abilitare', $serviziNames);
    $servizioId = $helper->ask($input, $output, $question);

    $serviziRepo = $this->entityManager->getRepository('App:Servizio');

    if ($servizioId != '*') {
      if (!$serviziRepo->find($servizioId)) {
        throw new InvalidArgumentException('Servizio '.$servizioId.' non trovato');
      }
      $servizio = $serviziRepo->find($servizioId);

      if ($serviziAbilitati->contains($servizio->getId())) {
        throw new InvalidArgumentException('Servizio '.$servizio->getName().' già abilitato');
      }
      $serviziAbilitati->add($servizio->getId());

      $user->setServiziAbilitati($serviziAbilitati);

      try {
        $this->entityManager->persist($user);
        $this->entityManager->flush();
        $output->writeln('Ok: utente '.$user->getUsername().' abilitato per il servizio '.$servizio->getName());

        return 0;

      } catch (Exception $e) {
        $output->writeln('Errore: '.$e->getMessage());

        return 1;
      }

    } else {
      foreach ($servizi as $servizio) {
        if (!$serviziAbilitati->contains($servizio->getId())) {
          $serviziAbilitati->add($servizio->getId());
        }
      }
      $user->setServiziAbilitati($serviziAbilitati);

      try {
        $this->entityManager->persist($user);
        $this->entityManager->flush();
        $output->writeln('Ok: utente '.$user->getUsername().' abilitato per tutti i servizi');

        return 0;

      } catch (Exception $e) {
        $output->writeln('Errore: '.$e->getMessage());

        return 1;
      }
    }
  }
}
