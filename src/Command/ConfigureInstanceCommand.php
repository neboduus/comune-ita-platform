<?php

namespace App\Command;

use App\DataFixtures\ORM\LoadData;
use App\Entity\AdminUser;
use App\Entity\Ente;
use App\Entity\PaymentGateway;
use App\Model\Gateway;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Cache\Exception\InvalidArgumentException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class ConfigureInstanceCommand extends Command
{
  /** @var LoadData */
  private $loader;

  /** @var EntityManagerInterface */
  private $entityManager;

  /** @var SymfonyStyle */
  private $symfonyStyle;

  /** @var UserPasswordEncoderInterface */
  private $passwordEncoder;

  public function __construct(
    EntityManagerInterface $entityManager,
    LoadData $loader,
    UserPasswordEncoderInterface $passwordEncoder
  ) {
    $this->loader = $loader;
    $this->entityManager = $entityManager;
    $this->passwordEncoder = $passwordEncoder;
    parent::__construct();
  }

  protected function configure()
  {
    $this
      ->setName('ocsdc:configure-instance')
      ->setDescription("Configurazione iniziale di un'istanza");
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $this->symfonyStyle = new SymfonyStyle($input, $output);
    $instance = $input->getOption('instance');
    if (empty($instance)) {
      throw new InvalidArgumentException("Devi specificare un'istanza");
    }

    $manager = $this->entityManager;
    $loader = $this->loader;
    $loader->loadPaymentGateways($manager);

    $repo = $manager->getRepository('App:Ente');
    $ente = $repo->findOneBySlug($instance);
    $isEnte = false;
    if ($ente instanceof Ente) {
      $isEnte = true;
    }

    $this->symfonyStyle->title("Inserisci i dati per la configurazione dell'istanza");
    $suggestion = $isEnte ? $ente->getName() : 'Comune di Bugliano';
    $name = $this->symfonyStyle->ask("Inserisci il nome dell'ente: ", $suggestion);

    $suggestion = $isEnte ? $ente->getCodiceMeccanografico() : '';
    $codeMec = $this->symfonyStyle->ask("Inserisci il codice Meccanografico: ", $suggestion);

    $suggestion = $isEnte ? $ente->getCodiceAmministrativo() : '';
    $codeAdm = $this->symfonyStyle->ask("Inserisci il codice Amministrativo: ", $suggestion);

    $suggestion = $isEnte ? $ente->getSiteUrl() : '';
    $url = $this->symfonyStyle->ask("Inserisci url del sito comunale: ", $suggestion);

    if (!$isEnte) {
      $ente = new Ente();
    }
    $ente
      ->setName($name)
      ->setCodiceMeccanografico($codeMec)
      ->setCodiceAmministrativo($codeAdm)
      ->setSiteUrl($url);

    /** @var PaymentGateway $bollo */
    $bollo = $manager->getRepository('App:PaymentGateway')->findOneByIdentifier('bollo');
    $gateway = new Gateway();
    $gateway->setIdentifier($bollo->getIdentifier());
    $gateway->setParameters(array('identifier' => $bollo->getIdentifier(), 'parameters' => null));

    $ente->setGateways(array('bollo' => $gateway));

    $manager->persist($ente);
    $manager->flush();

    $this->loader->loadCategories($manager);
    $this->loader->loadServizi($manager);
    $this->loader->loadTerminiUtilizzo($manager);

    if ($ente) {
      $this->createAdmin();
    }

    $this->symfonyStyle->success("Istanza configurata con successo");

    return 0;
  }

  private function createAdmin()
  {

    $this->symfonyStyle->title("Inserisci i dati per la creazione di un admin");
    $nome = $this->symfonyStyle->ask("Inserisci il nome: ", 'Opencontent');
    $cognome = $this->symfonyStyle->ask('Inserisci il cognome: ', 'Scarl');
    $email = $this->symfonyStyle->ask('Inserisci l\'indirizzo email: ', 'support@opencontent.it');
    $username = $this->symfonyStyle->ask('Inserisci lo username: ', 'admin');
    $password = $this->symfonyStyle->ask('Inserisci la password: ', '');

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

      $this->symfonyStyle->text('Ok: generato nuovo admin');
    } catch (\Exception $e) {
      $this->symfonyStyle->text('Errore: '.$e->getMessage());
    }
  }
}
