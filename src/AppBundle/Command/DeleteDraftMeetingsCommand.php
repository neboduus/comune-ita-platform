<?php

namespace AppBundle\Command;


use AppBundle\Entity\Meeting;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;


class DeleteDraftMeetingsCommand extends ContainerAwareCommand
{

  protected function configure()
  {
    $this
      ->setName('ocsdc:delete-draft-meetings')
      ->setDescription('Delete draft meetings');
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $logger = $this->getContainer()->get('logger');

    $logger->info('Start procedure for deleting draft meetings with options: ' . \json_encode($input->getOptions()));

    $em = $this->getContainer()->get('doctrine')->getManager();

    $meetings = $em->createQueryBuilder()
      ->select('meeting')
      ->from('AppBundle:Meeting', 'meeting')
      ->where('meeting.status = :status')
      ->andWhere('meeting.draftExpiration <= :date')
      ->setParameter('status', (Meeting::STATUS_DRAFT))
      ->setParameter('date', new \DateTime())
      ->getQuery()->getResult();

    if (empty($meetings)) {
      $logger->info("No meetings to remove");
    }

    foreach ($meetings as $meeting) {
      try {
        $em->remove($meeting);
        $em->flush();
        $logger->info("Successfully removed draft meeting " . $meeting->getId());
      } catch (\Exception $exception) {
        $logger->error(
          "An error occurred while removing draft meeting " . $meeting->getId() . ": " . $exception->getMessage()
        );
      }
    }
  }
}