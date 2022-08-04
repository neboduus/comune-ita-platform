<?php

namespace App\Command;

use App\Entity\OpeningHour;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;


class HotFixZeroMeetingQueueCommand extends Command
{

  /** @var EntityManagerInterface */
  private $entityManager;

  public function __construct(EntityManagerInterface $entityManager)
  {
    $this->entityManager = $entityManager;

    parent::__construct();
  }

  protected function configure()
  {
    $this
      ->setName('ocsdc:hotfix-meeting-queue')
      ->setDescription('Corregge gli orari di apertura in cui la coda dei meeting è impostata a zero');
  }


  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $io = new SymfonyStyle($input, $output);

    foreach ($this->getOpeningHours() as $openingHour) {
      if ($openingHour->getMeetingQueue() < 1) {

        $openingHour->setMeetingQueue(1);
        $this->entityManager->persist($openingHour);
        $this->entityManager->flush();

        $io->success('Fixed opening hour ' . $openingHour->getName() . ' for calendar ' . $openingHour->getCalendar()->getTitle());

      }
    }
  }


  /**
   * @return OpeningHour[]
   */
  private function getOpeningHours()
  {
    $repo = $this->entityManager->getRepository('App\Entity\OpeningHour');

    return $repo->findAll();
  }
}
