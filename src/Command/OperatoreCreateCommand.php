<?php

namespace App\Command;

use App\Entity\OperatoreUser;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Cache\Exception\InvalidArgumentException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

/**
 * Class OperatoreCreateCommand
 */
class OperatoreCreateCommand extends Command
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
      ->setName('ocsdc:crea-operatore')
      ->setDescription('Crea un record nella tabella utente di tipo operatore');
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $helper = $this->getHelper('question');

    $question = new Question('Inserisci il nome ', 'Mario');
    $nome = $helper->ask($input, $output, $question);

    $question = new Question('Inserisci il cognome ', 'Rossi');
    $cognome = $helper->ask($input, $output, $question);

    $question = new Question('Inserisci l\'indirizzo email ', 'gabriele@opencontent.it');
    $email = $helper->ask($input, $output, $question);

    $question = new Question('Inserisci lo username ', 'mariorossi');
    $username = $helper->ask($input, $output, $question);

    $question = new Question('Inserisci la password ', 'mariorossi');
    $password = $helper->ask($input, $output, $question);


    $repo = $this->entityManager->getRepository('App:Ente');
    $entiEntites = $repo->findAll();
    $enti = [];
    foreach ($entiEntites as $entiEntity) {
      $enti[] = $entiEntity->getName();
    }
    $question = new ChoiceQuestion('Seleziona ente di riferimento', $enti, 0);
    $enteName = $helper->ask($input, $output, $question);
    $ente = $repo->findOneByName($enteName);
    if (!$ente) {
      throw new InvalidArgumentException("Ente $enteName non trovato");
    }

    try {

      $user = (new OperatoreUser())
        ->setUsername($username)
        ->setEmail($email)
        ->setNome($nome)
        ->setEnte($ente)
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

      $output->writeln('Ok: generato nuovo operatore');
    } catch (\Exception $e) {
      $output->writeln('Errore: '.$e->getMessage());
    }
  }

}
