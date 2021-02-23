<?php

namespace AppBundle\Command;

use AppBundle\DataFixtures\ORM\LoadData;
use AppBundle\Entity\AdminUser;
use AppBundle\Entity\Ente;
use AppBundle\Entity\OperatoreUser;
use AppBundle\Entity\PaymentGateway;
use AppBundle\Entity\User;
use AppBundle\Model\Gateway;
use AppBundle\Utils\Csv;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Cache\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;

class ConfigureInstanceCommand extends ContainerAwareCommand
{

  /** @var */
  private $symfonyStyle;

  private $name;
  private $codeAdm;
  private $siteUrl;
  private $adminName;
  private $adminLastname;
  private $adminEmail;
  private $adminUsername;
  private $adminPassword;

  private $isInteractive = true;

  private $data = null;

  private $entityManager = null;

  private $output;

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

    $this->entityManager = $this->getContainer()->get('doctrine')->getManager();
    $ente = $this->creaateInstance($instance);
    if ($ente instanceof Ente) {
      $this->symfonyStyle->note("Ente creato correttamente: " . $ente->getName());
      $admin = $this->createAdmin($ente);
      if ($admin instanceof User) {
        $this->symfonyStyle->note("Admin creato correttamente: " . $admin->getUsername());
      }
    }

    // Scrivo il csv risultante solo se ho dato il file come parametro
    if (!$this->isInteractive) {
      $filesystem = new Filesystem();
      $file = $this->getContainer()->get('kernel')->getProjectDir() . '/var/uploads/instances-' . date('Ymd') . '.csv';
      if (!is_file($file)) {
        $filesystem->touch($file);
      }
      $fp = fopen($file, 'a');
      foreach ($this->output as $item) {
        fputcsv($fp, $item);
      }
      fclose($fp);
    }

    $this->symfonyStyle->success("Istanza configurata con successo: " . $instance);
  }

  /**
   * @param $identifier
   * @return Ente|mixed
   */
  private function creaateInstance($identifier)
  {
    $instanceExists = false;
    $repo = $this->entityManager->getRepository('AppBundle:Ente');
    $ente = $repo->findOneBySlug($identifier);

    if ( $ente instanceof Ente ) {
      $instanceExists = true;
    }

    if (!$this->isInteractive) {
      $name = $this->name;
      $codeMec = $this->codeAdm ?? '';
      $codeAdm = $this->codeAdm ?? '';
      $url = $this->siteUrl;
    } else {

      $this->symfonyStyle->title("Inserisci i dati per la configurazione dell'istanza");
      $suggestion = $instanceExists ? $ente->getName() : 'Comune di Bugliano';
      $name = $this->symfonyStyle->ask("Inserisci il nome dell'ente: ", $suggestion);

      $suggestion = $instanceExists ? $ente->getCodiceMeccanografico() : '';
      $codeMec = $this->symfonyStyle->ask("Inserisci il codice Meccanografico: ", $suggestion);

      $suggestion = $instanceExists ? $ente->getCodiceAmministrativo() : '';
      $codeAdm = $this->symfonyStyle->ask("Inserisci il codice Amministrativo: ", $suggestion);

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
      ->setCodiceMeccanografico($codeMec)
      ->setCodiceAmministrativo($codeAdm)
      ->setSiteUrl($url);

    // Altre configurazioni
    $loader = new LoadData();
    $loader->setContainer($this->getContainer());
    $loader->loadPaymentGateways($this->entityManager);

    /** @var PaymentGateway $bollo */
    $bollo = $this->entityManager->getRepository('AppBundle:PaymentGateway')->findOneByIdentifier('bollo');
    $gateway = new Gateway();
    $gateway->setIdentifier($bollo->getIdentifier());
    $gateway->setParameters(array('identifier' => $bollo->getIdentifier(), 'parameters' => null));

    $ente->setGateways(array('bollo' => $gateway));

    $this->entityManager->persist($ente);
    $this->entityManager->flush();

    if ($instanceExists) {
      $loader->loadCategories($this->entityManager);
      $loader->loadTerminiUtilizzo($this->entityManager);
    }

    return $ente;

  }

  /**
   * @return AdminUser|\FOS\UserBundle\Model\UserInterface|null
   */
  private function createAdmin(Ente $ente) {

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
      $username = $this->symfonyStyle->ask('Inserisci lo username: ', 'admin');
      $password = $this->symfonyStyle->ask('Inserisci la password: ', '');
    }

    $repo = $this->entityManager->getRepository('AppBundle:AdminUser');
    $user = $repo->findOneByUsername($username);

    if ( !$user instanceof User ) {
      $user = new AdminUser();
    }

    $um = $this->getContainer()->get('fos_user.user_manager');
    if (empty($password)) {
      $password = $this->randomPassword();
    }

    $user
      ->setUsername($username)
      ->setPlainPassword($password)
      ->setEmail($email)
      ->setNome($nome)
      ->setCognome($cognome)
      ->setEnabled(true);

    try {
      $um->updateUser($user);
      $this->output []= [$ente->getName(), $user->getUsername(), $password];

      return $user;
    } catch (\Exception $e) {
      $this->symfonyStyle->text('Errore: ' . $e->getMessage());
      return null;
    }
  }

  /**
   * @return string
   */
  private function randomPassword()
  {
    $alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
    $pass = array(); //remember to declare $pass as an array
    $alphaLength = strlen($alphabet) - 1; //put the length -1 in cache
    for ($i = 0; $i < 8; $i++) {
      $n = rand(0, $alphaLength);
      $pass[] = $alphabet[$n];
    }
    return implode($pass); //turn the array into a string
  }
}
