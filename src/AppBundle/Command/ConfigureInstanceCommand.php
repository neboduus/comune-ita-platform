<?php

namespace AppBundle\Command;

use AppBundle\DataFixtures\ORM\LoadData;
use AppBundle\Entity\AdminUser;
use AppBundle\Entity\Ente;
use AppBundle\Entity\OperatoreUser;
use AppBundle\Entity\PaymentGateway;
use AppBundle\Model\Gateway;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Cache\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;

class ConfigureInstanceCommand extends ContainerAwareCommand
{

  /**
   * @var
   */
  private $symfonyStyle;

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

    $manager = $this->getContainer()->get('doctrine')->getManager();
    $loader = new LoadData();
    $loader->setContainer($this->getContainer());
    $loader->loadPaymentGateways($manager);

    $repo = $manager->getRepository('AppBundle:Ente');
    $ente = $repo->findOneBySlug($instance);
    $isEnte = false;
    if ( $ente instanceof Ente ) {
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
    $bollo = $manager->getRepository('AppBundle:PaymentGateway')->findOneByIdentifier('bollo');
    $gateway = new Gateway();
    $gateway->setIdentifier($bollo->getIdentifier());
    $gateway->setParameters(array('identifier' => $bollo->getIdentifier(), 'parameters' => null));

    $ente->setGateways(array('bollo' => $gateway));

    $manager->persist($ente);
    $manager->flush();

    $loader = new LoadData();
    $loader->setContainer($this->getContainer());
    $loader->loadCategories($manager);
    $loader->loadServizi($manager);
    $loader->loadTerminiUtilizzo($manager);


    if ($ente) {
      $this->createAdmin();
    }

    $this->symfonyStyle->success("Istanza configurata con successo");
  }

  private function createAdmin() {

    $this->symfonyStyle->title("Inserisci i dati per la creazione di un admin");
    $nome = $this->symfonyStyle->ask("Inserisci il nome: ", 'Opencontent');
    $cognome = $this->symfonyStyle->ask('Inserisci il cognome: ', 'Scarl');
    $email = $this->symfonyStyle->ask('Inserisci l\'indirizzo email: ', 'support@opencontent.it');
    $username = $this->symfonyStyle->ask('Inserisci lo username: ', 'admin');
    $password = $this->symfonyStyle->ask('Inserisci la password: ', '');

    $um = $this->getContainer()->get('fos_user.user_manager');

    $user = (new AdminUser())
      ->setUsername($username)
      ->setPlainPassword($password)
      ->setEmail($email)
      ->setNome($nome)
      ->setCognome($cognome)
      ->setEnabled(true);

    try {
      $um->updateUser($user);
      $this->symfonyStyle->text('Ok: generato nuovo admin');
    } catch (\Exception $e) {
      $this->symfonyStyle->text('Errore: ' . $e->getMessage());
    }
  }
}
