<?php

namespace App\Command;

use App\Entity\AdminUser;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

/**
 * Class OperatoreCreateCommand
 */
class AdministratorCreateCommand extends Command
{
  /** @var EntityManagerInterface */
  private $entityManager;

  /** @var UserPasswordEncoderInterface */
  private $passwordEncoder;

  /**
   * AdministratorCreateCommand constructor.
   * @param EntityManagerInterface $entityManager
   * @param UserPasswordEncoderInterface $passwordEncoder
   */
  public function __construct(EntityManagerInterface $entityManager, UserPasswordEncoderInterface $passwordEncoder)
  {
    $this->entityManager = $entityManager;
    $this->passwordEncoder = $passwordEncoder;
    parent::__construct();
  }

  protected function configure()
  {
    $this
      ->setName('ocsdc:crea-admin')
      ->setDescription('Crea un record nella tabella utente di tipo admin');
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $helper = $this->getHelper('question');

    $question = new Question('Inserisci il nome ', 'Mario');
    $nome = $helper->ask($input, $output, $question);

    $question = new Question('Inserisci il cognome ', 'Rossi');
    $cognome = $helper->ask($input, $output, $question);

    $question = new Question('Inserisci l\'indirizzo email ', 'support@opencontent.it');
    $email = $helper->ask($input, $output, $question);

    $question = new Question('Inserisci lo username ', 'mariorossi');
    $username = $helper->ask($input, $output, $question);

    $question = new Question('Inserisci la password ', 'mariorossi');
    $password = $helper->ask($input, $output, $question);


    try {
      $user = (new AdminUser())
        ->setUsername($username)
        ->setEmail($email)
        ->setNome($nome)
        ->setCognome($cognome)
        ->setEnabled(true);

      $user->setPassword(
        $this->passwordEncoder->encodePassword(
          $user,
          $password
        )
      );
      $this->entityManager->persist($user);
      $this->entityManager->flush();
      $output->writeln('Ok: generato nuovo admin');
    } catch (\Exception $e) {
      $output->writeln('Errore: '.$e->getMessage());
    }
  }

}
