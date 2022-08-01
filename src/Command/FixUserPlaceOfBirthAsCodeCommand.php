<?php

namespace App\Command;

use App\Helpers\MunicipalityConverter;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class FixUserPlaceOfBirthAsCodeCommand extends ContainerAwareCommand
{

  protected function configure()
  {
    $this
      ->setName('ocsdc:fix-user-place-of-birth-as-code')
      ->setDescription('Sostituisce il codice del luogo di nascita con il nome completo');
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $codes = array_keys(MunicipalityConverter::getCodes());

    /** @var EntityManager $entityManager */
    $entityManager = $this->getContainer()->get('doctrine')->getManager();
    $users = $entityManager->getRepository('App:CPSUser')->findBy(['luogoNascita' => $codes]);

    foreach ($users as $user) {
      if ($user->getLuogoNascita()) {
        $old = $user->getLuogoNascita();
        try {
          $new = MunicipalityConverter::translate($old);
          $user->setLuogoNascita($new);
          $output->writeln('Utente ' . $user->getUsername() . ' - Sostituisco ' . $old. ' con ' . $new);
          $entityManager->persist($user);
          $entityManager->flush();
        } catch (\Exception $e) {
          $output->writeln('Utente ' . $user->getUsername() . ' - ' . $e->getMessage());
        }
      }
    }
  }
}
