<?php

namespace AppBundle\Command;

use AppBundle\Entity\OperatoreUser;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Cache\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;

/**
 * Class OperatoreAbilitaServizio
 */
class OperatoreAbilitaServizioCommand extends ContainerAwareCommand
{
  protected function configure()
  {
    $this
      ->setName('ocsdc:abilita-operatore-per-servizio')
      ->setDescription("Abilita l'operatore passato per i servizi")
      ->addOption('username', null, InputOption::VALUE_OPTIONAL, 'Username')
      ->addOption('all', null, InputOption::VALUE_NONE, "Abilita l'utente per tutti i servizi presenti");
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {

    $isInteractive = true;
    $username = $input->getOption('username');
    if (!empty($username)) {
      $isInteractive = false;
    }

    $helper = $this->getHelper('question');

    if ($isInteractive) {
      $question = new Question('Inserisci lo username ');
      $username = $helper->ask($input, $output, $question);
    }

    $em = $this->getContainer()->get('doctrine')->getManager();
    $operatoriRepo = $em->getRepository('AppBundle:OperatoreUser');
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

    if ($input->getOption('all')) {
      $servizioId = '*';
    } else {
      $question = new ChoiceQuestion('Seleziona il servizio da abilitare', $serviziNames);
      $servizioId = $helper->ask($input, $output, $question);
    }

    $serviziRepo = $em->getRepository('AppBundle:Servizio');
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
      $um = $this->getContainer()->get('fos_user.user_manager');

      try {
        $um->updateUser($user);
        $output->writeln('Ok: utente '.$user->getUsername().' abilitato per il servizio '.$servizio->getName());
      } catch (\Exception $e) {
        $output->writeln('Errore: '.$e->getMessage());
      }

    } else {
      foreach ($servizi as $servizio) {
        if (!$serviziAbilitati->contains($servizio->getId())) {
          $serviziAbilitati->add($servizio->getId());
        }
      }
      $user->setServiziAbilitati($serviziAbilitati);
      $um = $this->getContainer()->get('fos_user.user_manager');

      try {
        $um->updateUser($user);
        $output->writeln('Ok: utente '.$user->getUsername().' abilitato per tutti i servizi');
      } catch (\Exception $e) {
        $output->writeln('Errore: '.$e->getMessage());
      }
    }
  }

}
