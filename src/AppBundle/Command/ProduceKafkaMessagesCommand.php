<?php

namespace AppBundle\Command;


use AppBundle\Entity\Meeting;
use AppBundle\Entity\Pratica;
use Cassandra\Date;
use DateTime;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpFoundation\Response;


class ProduceKafkaMessagesCommand extends ContainerAwareCommand
{

  protected function configure()
  {
    $this
      ->setName('ocsdc:kafka:produce-messages')
      ->addOption('date', null, InputOption::VALUE_OPTIONAL, 'If specified, only applications with an update date before to the date entered will be considered. Format yyyy-mm-dd')
      ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Dry run')
      ->setDescription('Produce kafka messages from applications');
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {

    $this->symfonyStyle = new SymfonyStyle($input, $output);

    $date = $input->getOption('date');
    $dryRun = $input->getOption('dry-run');

    if (!empty($date)) {
      $dateFormat = 'Y-m-d';
      $date = DateTime::createFromFormat($dateFormat, $date);
      if (!$date || $date->format($dateFormat) !== $date) {
        $this->symfonyStyle->error('Option date must be this format: yyyy-mm-dd');
        return 1;
      }
    }

    $em = $this->getContainer()->get('doctrine')->getManager();
    $notAllowedStatuses = [Pratica::STATUS_DRAFT];

    $qb = $em->createQueryBuilder()
      ->select('pratica')
      ->from('AppBundle:Pratica', 'pratica')
      ->where('pratica.status NOT IN (:status)')
      ->setParameter('status', $notAllowedStatuses);

    if ($date instanceof DateTime) {
      $qb->andWhere('pratica.updated_at <= :date')
        ->setParameter('date', $date);
    }

    $applications = $qb->getQuery()->getResult();
    $this->symfonyStyle->note('Will be created ' . count($applications) . ' messages');

    $messages = 0;

    if (!empty($applications)) {
      $kafkaService = $this->getContainer()->get('ocsdc.kafka_service');
      /** @var Pratica $application */
      foreach ($applications as $application) {
        if (!$dryRun) {
          $kafkaService->produceMessage($application);
          $messages++;
        }
      }
    }

    $this->symfonyStyle->success('Success! - Messages created: ' . $messages );
  }
}