<?php

namespace App\Command;


use App\Entity\Meeting;
use App\Services\Manager\PraticaManager;
use App\Services\SubscriptionsService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;


class DeleteDraftMeetingsCommand extends Command
{

  /**
   * @var EntityManagerInterface
   */
  private $entityManager;
  /**
   * @var LoggerInterface
   */
  private $logger;

  /**
   * @param EntityManagerInterface $entityManager
   * @param LoggerInterface $logger
   */
  public function __construct(EntityManagerInterface $entityManager, LoggerInterface $logger)
  {
    $this->entityManager = $entityManager;
    $this->logger = $logger;
    parent::__construct();
  }

  protected function configure()
  {
    $this
      ->setName('ocsdc:delete-draft-meetings')
      ->setDescription('Delete draft meetings');
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {

    $this->logger->info('Start procedure for deleting draft meetings with options: ' . \json_encode($input->getOptions()));

    $meetings = $this->entityManager->createQueryBuilder()
      ->select('meeting')
      ->from('App:Meeting', 'meeting')
      ->where('meeting.status = :status')
      ->andWhere('meeting.draftExpiration <= :date')
      ->setParameter('status', (Meeting::STATUS_DRAFT))
      ->setParameter('date', new \DateTime())
      ->getQuery()->getResult();

    if (empty($meetings)) {
      $this->logger->info("No meetings to remove");
    }

    foreach ($meetings as $meeting) {
      try {
        $this->entityManager->remove($meeting);
        $this->entityManager->flush();
        $this->logger->info("Successfully removed draft meeting " . $meeting->getId());
      } catch (\Exception $exception) {
        $this->logger->error("An error occurred while removing draft meeting " . $meeting->getId() . ": " . $exception->getMessage());
      }
    }

    return 0;
  }
}
