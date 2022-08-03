<?php

namespace App\Command;

use App\Helpers\MunicipalityConverter;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class FixUserPlaceOfBirthAsCodeCommand extends Command
{

  /**
   * @var EntityManagerInterface
   */
  private $entityManager;

  /**
   * @param EntityManagerInterface $entityManager
   */
  public function __construct(EntityManagerInterface $entityManager)
  {
    parent::__construct();
    $this->entityManager = $entityManager;
  }

  protected function configure()
  {
    $this
      ->setName('ocsdc:fix-user-place-of-birth-as-code')
      ->setDescription('Sostituisce il codice del luogo di nascita con il nome completo');
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $codes = array_keys(MunicipalityConverter::getCodes());

    $users = $this->entityManager->getRepository('App\Entity\CPSUser')->findBy(['luogoNascita' => $codes]);

    foreach ($users as $user) {
      if ($user->getLuogoNascita()) {
        $old = $user->getLuogoNascita();
        try {
          $new = MunicipalityConverter::translate($old);
          $user->setLuogoNascita($new);
          $output->writeln('Utente ' . $user->getUsername() . ' - Sostituisco ' . $old. ' con ' . $new);
          $this->entityManager->persist($user);
          $this->entityManager->flush();
        } catch (\Exception $e) {
          $output->writeln('Utente ' . $user->getUsername() . ' - ' . $e->getMessage());
        }
      }
    }
  }
}
