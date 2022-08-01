<?php

namespace App\Command;

use App\Entity\OperatoreUser;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Cache\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

/**
 * Class OperatoreCreateCommand
 */
class OperatoreCreateCommand extends Command
{

  /** @var EntityManagerInterface */
  private $entityManager;
  /**
   * @var UserPasswordEncoderInterface
   */
  private $passwordEncoder;

  /**
   * @param EntityManagerInterface $entityManager
   * @param UserPasswordEncoderInterface $passwordEncoder
   */
  public function __construct(EntityManagerInterface $entityManager, UserPasswordEncoderInterface $passwordEncoder)
  {
    $this->entityManager = $entityManager;
    parent::__construct();
    $this->passwordEncoder = $passwordEncoder;
  }

  protected function configure()
  {
    $this
      ->setName('ocsdc:crea-operatore')
      ->setDescription('Crea un record nella tabella utente di tipo operatore')
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

      $question = new Question('Inserisci il nome: ', 'Mario');
      $nome = $helper->ask($input, $output, $question);

      $question = new Question('Inserisci il cognome: ', 'Rossi');
      $cognome = $helper->ask($input, $output, $question);

      $question = new Question('Inserisci l\'indirizzo email: ', 'operatore@email.it');
      $email = $helper->ask($input, $output, $question);

      $question = new Question('Inserisci lo username: ', 'mariorossi');
      $username = $helper->ask($input, $output, $question);

      $question = new Question('Inserisci la password: ', 'password');
      $password = $helper->ask($input, $output, $question);
    }

    $repo = $this->entityManager->getRepository('App\Entity\Ente');
    $ente = $repo->findOneBySlug($instance);

    if (!$ente) {
      throw new InvalidArgumentException("Ente non trovato");
    }

    $userRepo = $this->entityManager->getRepository('App\Entity\OperatoreUser');
    $user = $userRepo->findOneByUsername($username);

    if ( !$user instanceof User ) {
      $user = new OperatoreUser();
    }

    $user->setUsername($username)
      ->setPlainPassword($password)
      ->setEmail($email)
      ->setNome($nome)
      ->setEnte($ente)
      ->setCognome($cognome)
      ->setEnabled(true)
      ->setLastChangePassword(new \DateTime());

    try {
      $user->setPassword(
        $this->passwordEncoder->encodePassword(
          $user,
          $password
        )
      );
      $this->entityManager->persist($user);
      $this->entityManager->flush();
      $output->writeln('Ok: generato nuovo operatore');
    } catch (\Exception $e) {
      $output->writeln('Errore: '.$e->getMessage());
      return 1;
    }

    return 0;
  }

}
