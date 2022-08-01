<?php

namespace App\Command;

use App\Entity\OperatoreUser;
use App\Entity\Servizio;
use Doctrine\ORM\EntityManagerInterface;
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
      ->addOption('services', null, InputOption::VALUE_OPTIONAL, 'Lista degli slug dei servizi separata da |')
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

    /** @var EntityManagerInterface $em */
    $em = $this->getContainer()->get('doctrine')->getManager();

    $operatoriRepo = $em->getRepository('App:OperatoreUser');
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

    $serviziRepo = $em->getRepository('App:Servizio');

    if (empty($input->getOption('all')) && empty($input->getOption('services'))) {

      $question = new ChoiceQuestion('Seleziona il servizio da abilitare', $serviziNames);
      $servizioId = $helper->ask($input, $output, $question);
      if (!$serviziRepo->find($servizioId)) {
        throw new InvalidArgumentException('Servizio '.$servizioId.' non trovato');
      }
      $servizio = $serviziRepo->find($servizioId);

      if ($serviziAbilitati->contains($servizio->getId())) {
        throw new InvalidArgumentException('Servizio '.$servizio->getName().' già abilitato');
      }
      $serviziAbilitati->add($servizio->getId());
      $user->setServiziAbilitati($serviziAbilitati);

    } else {
      if ($input->getOption('all')) {

        foreach ($servizi as $servizio) {
          if (!$serviziAbilitati->contains($servizio->getId())) {
            $serviziAbilitati->add($servizio->getId());
          }
        }
        $user->setServiziAbilitati($serviziAbilitati);

      } elseif ($input->getOption('services')) {

        $services = explode('|', $input->getOption('services'));
        foreach ($services as $serviceSlug) {
          $servizio = $serviziRepo->findOneBy(['slug' => $serviceSlug]);
          if (! $servizio instanceof Servizio) {
            throw new InvalidArgumentException($serviceSlug . ' non è un servizio presente su questa istanza.');
          }
          if (!$serviziAbilitati->contains($servizio->getId())) {
            $serviziAbilitati->add($servizio->getId());
          }
        }

        $user->setServiziAbilitati($serviziAbilitati);
      }
    }

    $um = $this->getContainer()->get('fos_user.user_manager');
    try {
      $um->updateUser($user);
      $output->writeln('Ok: utente '.$user->getUsername().' aggiornato correttamente');
    } catch (\Exception $e) {
      $output->writeln('Errore: '.$e->getMessage());
    }


  }

}
