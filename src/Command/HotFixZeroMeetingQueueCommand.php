<?php

namespace App\Command;

use App\Entity\OpeningHour;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;


class HotFixZeroMeetingQueueCommand extends ContainerAwareCommand
{
  /**
   * @var EntityManager
   */
  private $em;

  /**
   * @var SymfonyStyle
   */
  private $io;

  protected function configure()
  {
    $this
      ->setName('ocsdc:hotfix-meeting-queue')
      ->setDescription('Corregge gli orari di apertura in cui la coda dei meeting è impostata a zero');
  }


  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $this->em = $this->getContainer()->get('doctrine')->getManager();
    $this->io = new SymfonyStyle($input, $output);

    foreach ($this->getOpeningHours() as $openingHour) {
      if ($openingHour->getMeetingQueue() < 1) {

        $openingHour->setMeetingQueue(1);
        $this->em->persist($openingHour);
        $this->em->flush();

        $output->writeln('Fixed opening hour ' . $openingHour->getName() . ' for calendar ' . $openingHour->getCalendar()->getTitle());

      }
    }
  }


  /**
   * @return OpeningHour[]
   */
  private function getOpeningHours()
  {
    $repo = $this->em->getRepository('App:OpeningHour');

    return $repo->findAll();
  }
}
