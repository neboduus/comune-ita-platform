<?php

namespace App\Command;

use App\DataFixtures\ORM\LoadData;
use App\Entity\AdminUser;
use App\Entity\Ente;
use App\Entity\OperatoreUser;
use App\Entity\User;
use App\Model\Gateway;
use App\Services\Satisfy\SatisfyTenant;
use App\Utils\Csv;
use App\Utils\StringUtils;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Cache\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class ConfigureInstanceCommand extends Command
{

  private SymfonyStyle $symfonyStyle;

  private $name;

  private $codeAdm;
  private $siteUrl;
  private $adminName;
  private $adminLastname;
  private $adminEmail;
  private $adminUsername;
  private $adminPassword;

  private bool $isInteractive = true;

  private EntityManagerInterface $entityManager;

  private UserPasswordEncoderInterface $passwordEncoder;
  private SatisfyTenant $satisfyTenant;

  /**
   * @param EntityManagerInterface $entityManager
   * @param UserPasswordEncoderInterface $passwordEncoder
   */
  public function __construct(EntityManagerInterface $entityManager, UserPasswordEncoderInterface $passwordEncoder, SatisfyTenant $satisfyTenant)
  {
    $this->entityManager = $entityManager;
    $this->passwordEncoder = $passwordEncoder;
    $this->satisfyTenant = $satisfyTenant;
    parent::__construct();

  }

  protected function configure()
  {
    $this
      ->setName('ocsdc:configure-instance')
      ->addOption('name', null, InputOption::VALUE_OPTIONAL, 'Name of the instance')
      ->addOption('code_adm', null, InputOption::VALUE_OPTIONAL, 'Administration code of the instance')
      ->addOption('siteurl', null, InputOption::VALUE_OPTIONAL, 'Site url of the instance')
      ->addOption('admin_name', null, InputOption::VALUE_OPTIONAL, 'Name of the admin')
      ->addOption('admin_lastname', null, InputOption::VALUE_OPTIONAL, 'Lastname of the admin')
      ->addOption('admin_email', null, InputOption::VALUE_OPTIONAL, 'Email of the admin')
      ->addOption('admin_username', null, InputOption::VALUE_OPTIONAL, 'Username of the admin')
      ->addOption('admin_password', null, InputOption::VALUE_OPTIONAL, 'Username of the admin')
      ->setDescription("Initial settings of an instance");
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $this->symfonyStyle = new SymfonyStyle($input, $output);
    $instance = $input->getOption('instance');
    if (empty($instance)) {
      throw new InvalidArgumentException("Devi specificare un'istanza");
    }

    $this->name = $input->getOption('name');
    $this->codeAdm = $input->getOption('code_adm');
    $this->siteUrl = $input->getOption('siteurl');
    $this->adminName = $input->getOption('admin_name');
    $this->adminLastname = $input->getOption('admin_lastname');
    $this->adminEmail = $input->getOption('admin_email');
    $this->adminUsername = $input->getOption('admin_username');
    $this->adminPassword = $input->getOption('admin_password');

    if (!empty($this->name) && !empty($this->codeAdm) && !empty($this->siteUrl) && !empty($this->adminEmail) && !empty($this->adminUsername)) {
      $this->isInteractive = false;
    }

    $ente = $this->creaateInstance($instance);
    if ($ente instanceof Ente) {
      $this->symfonyStyle->note("Ente creato correttamente: " . $ente->getName());
      $admin = $this->createAdmin($ente);
      if ($admin instanceof User) {
        $this->symfonyStyle->note("Admin creato correttamente: " . $admin->getUsername());
      }

      if ($this->createSatisfyTenant($ente)) {
        $this->symfonyStyle->note("Satisfy creato correttamente: ");
      }

      $this->createBuiltinServices($instance, $output);
    }

    $this->symfonyStyle->success("Istanza configurata con successo: " . $instance);

    return 0;
  }

  /**
   * @param $identifier
   * @return Ente|mixed
   */
  private function creaateInstance($identifier)
  {
    $instanceExists = false;
    $repo = $this->entityManager->getRepository('App\Entity\Ente');
    $ente = $repo->findOneBySlug($identifier);

    if ( $ente instanceof Ente ) {
      $instanceExists = true;
    }

    if (!$this->isInteractive) {
      $name = $this->name;
      $codeAdm = $this->codeAdm ?? '';
      $url = $this->siteUrl;
    } else {

      $this->symfonyStyle->title("Inserisci i dati per la configurazione dell'istanza");
      $suggestion = $instanceExists ? $ente->getName() : 'Comune di Bugliano';
      $name = $this->symfonyStyle->ask("Inserisci il nome dell'ente: ", $suggestion);

      $suggestion = $instanceExists ? $ente->getCodiceMeccanografico() : '';
      $codeAdm = $this->symfonyStyle->ask("Inserisci il codice Meccanografico/Amministrativo: ", $suggestion);

      $suggestion = $instanceExists ? $ente->getSiteUrl() : '';
      $url = $this->symfonyStyle->ask("Inserisci url del sito comunale: ", $suggestion);
    }

    // Creo
    if (!$instanceExists) {
      $ente = new Ente();
    }
    $ente
      ->setName($name)
      //->setSlug($identifier)
      ->setCodiceMeccanografico($codeAdm)
      ->setCodiceAmministrativo($codeAdm)
      ->setSiteUrl($url);

    // Altre configurazioni
    $loader = new LoadData();
    $this->entityManager->persist($ente);
    $this->entityManager->flush();

    $loader->loadCategories($this->entityManager);
    $loader->loadTerminiUtilizzo($this->entityManager);

    return $ente;

  }

  /**
   * @return AdminUser
   */
  private function createAdmin(Ente $ente)
  {

    if (!$this->isInteractive) {
      $nome = $this->adminName ?? '';
      $cognome = $this->adminLastname ?? '';
      $email = $this->adminEmail;
      $username = $this->adminUsername;
      $password = $this->adminPassword;
    } else {
      $this->symfonyStyle->title("Inserisci i dati per la creazione di un admin");
      $nome = $this->symfonyStyle->ask("Inserisci il nome: ", 'Opencontent');
      $cognome = $this->symfonyStyle->ask('Inserisci il cognome: ', 'Scarl');
      $email = $this->symfonyStyle->ask('Inserisci l\'indirizzo email: ', 'support@opencontent.it');
      $this->adminEmail = $email;
      $username = $this->symfonyStyle->ask('Inserisci lo username: ', 'admin');
      $password = $this->symfonyStyle->ask('Inserisci la password: ', StringUtils::randomPassword());
      $this->adminPassword = $password;
    }

    $repo = $this->entityManager->getRepository('App\Entity\AdminUser');
    $user = $repo->findOneByUsername($username);

    if ( !$user instanceof User ) {
      $user = new AdminUser();
    }

    if (empty($password)) {
      $password = StringUtils::randomPassword();
    }

    $user
      ->setUsername($username)
      ->setPlainPassword($password)
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

    try {
      $this->entityManager->persist($user);
      $this->entityManager->flush();

      return $user;
    } catch (\Exception $e) {
      $this->symfonyStyle->text('Errore: ' . $e->getMessage());
      return null;
    }
  }

  private function createSatisfyTenant(Ente $ente)
  {
    $createSatisfyTenant = $this->symfonyStyle->confirm("Vuoi procedere con la configurazione di satisfy per il tenant corrente?");
    if ($createSatisfyTenant) {

      if (!empty($ente->getSatisfyEntrypointId())) {
        $this->symfonyStyle->warning("Su l'ente corrente è già presente un id di configurazione di Satisfy, verificare!");
        return false;
      }

      try {
        $password = hash('sha256', $this->adminPassword);
        return $this->satisfyTenant->createEntryPoint($ente, $this->adminEmail, $password);
      } catch (\Exception $e) {
        $this->symfonyStyle->error($e->getMessage());
      }
    }
    return false;
  }

  private function createBuiltinServices($instance, $output)
  {

    $createBuiltinServices = $this->symfonyStyle->confirm("Vuoi procedere con la creazione dei servizi Buitin?");
    if ($createBuiltinServices) {
      $command = $this->getApplication()->find('ocsdc:built-in-services-import');

      foreach (['bookings', 'helpdesk', 'inefficiencies'] as $s) {
        $arguments = [
          '-i'    => $instance,
          '-f'  => './data/built-in/'. $s .'.json',
        ];

        $greetInput = new ArrayInput($arguments);
        $command->run($greetInput, $output);
      }
    }
  }
}
