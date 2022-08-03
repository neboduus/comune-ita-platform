<?php

namespace App\Command;

use App\Entity\AdminUser;
use App\Entity\User;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Cache\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

/**
 * Class OperatoreCreateCommand
 */
class AdministratorCreateCommand extends Command
{
  protected function configure()
  {
    $this
      ->setName('ocsdc:crea-admin')
      ->setDescription('Crea un record nella tabella utente di tipo admin')
      ->addOption('name', null, InputOption::VALUE_OPTIONAL, 'Nome')
      ->addOption('lastname', null, InputOption::VALUE_OPTIONAL, 'Cognome')
      ->addOption('email', null, InputOption::VALUE_OPTIONAL, 'Email')
      ->addOption('username', null, InputOption::VALUE_OPTIONAL, 'Username')
      ->addOption('password', null, InputOption::VALUE_OPTIONAL, 'Password');
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {

    $instance = $input->getOption('instance');
    $isInteractive = true;

    $nome = $input->getOption('name');
    $cognome = $input->getOption('lastname');
    $email = $input->getOption('email');
    $username = $input->getOption('username');
    $password = $input->getOption('password');

    if (!empty($nome) && !empty($cognome) && !empty($email) && !empty($username) && !empty($password)) {
      $isInteractive = false;
    }

    if ($isInteractive) {
      $helper = $this->getHelper('question');

      $question = new Question('Inserisci il nome ', 'Mario');
      $nome = $helper->ask($input, $output, $question);

      $question = new Question('Inserisci il cognome ', 'Rossi');
      $cognome = $helper->ask($input, $output, $question);

      $question = new Question('Inserisci l\'indirizzo email ', 'admin@email.it');
      $email = $helper->ask($input, $output, $question);

      $question = new Question('Inserisci lo username ', 'mariorossi');
      $username = $helper->ask($input, $output, $question);

      $question = new Question('Inserisci la password ', 'mariorossi');
      $password = $helper->ask($input, $output, $question);
    }

    $em = $this->getContainer()->get('doctrine')->getManager();
    $repo = $em->getRepository('App\Entity\Ente');
    $ente = $repo->findOneBySlug($instance);

    if (!$ente) {
      throw new InvalidArgumentException("Ente non trovato");
    }

    $um = $this->getContainer()->get('fos_user.user_manager');
    $userRepo = $em->getRepository('App\Entity\AdminUser');
    $user = $userRepo->findOneByUsername($username);

    if ( !$user instanceof User ) {
      $user = new AdminUser();
    }

    $user
      ->setUsername($username)
      ->setPlainPassword($password)
      ->setEmail($email)
      ->setNome($nome)
      ->setCognome($cognome)
      ->setEnte($ente)
      ->setEnabled(true);

    try {
      $um->updateUser($user);
      $output->writeln('Ok: generato nuovo admin');
    } catch (\Exception $e) {
      $output->writeln('Errore: '.$e->getMessage());
    }
  }

}
