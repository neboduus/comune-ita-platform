<?php

namespace App\Command;


use App\Entity\Meeting;
use App\Entity\Pratica;
use Cassandra\Date;
use DateTime;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpFoundation\Response;


class ProduceKafkaMessagesCommand extends Command
{

  protected function configure()
  {
    $this
      ->setName('ocsdc:kafka:produce-messages')
      ->addOption('id', null, InputOption::VALUE_OPTIONAL, 'Id of the application')
      ->addOption('date', null, InputOption::VALUE_OPTIONAL, 'If specified, only applications with an update date before to the date entered will be considered. Format yyyy-mm-dd')
      ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Dry run')
      ->setDescription('Produce kafka messages from applications');
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {

    $symfonyStyle = new SymfonyStyle($input, $output);

    $context = $this->getContainer()->get('router')->getContext();
    $context->setHost($this->getContainer()->getParameter('ocsdc_host'));
    $context->setScheme($this->getContainer()->getParameter('ocsdc_scheme'));

    $id = $input->getOption('id');
    $date = $input->getOption('date');
    $dryRun = $input->getOption('dry-run');

    if (!empty($date)) {
      $dateFormat = 'Y-m-d';
      $date = DateTime::createFromFormat($dateFormat, $date);
      if (!$date || $date->format($dateFormat) !== $date) {
        $symfonyStyle->error('Option date must be this format: yyyy-mm-dd');
        return 1;
      }
    }

    $em = $this->getContainer()->get('doctrine')->getManager();
    $notAllowedStatuses = [Pratica::STATUS_DRAFT];

    $qb = $em->createQueryBuilder()
      ->select('pratica')
      ->from('App:Pratica', 'pratica')
      ->where('pratica.status NOT IN (:status)')
      ->setParameter('status', $notAllowedStatuses);

    if (!empty($id)) {
      $qb->andWhere('pratica.id = :id')
        ->setParameter('id', $id);
    }

    if ($date instanceof DateTime) {
      $qb->andWhere('pratica.updated_at <= :date')
        ->setParameter('date', $date);
    }

    $applications = $qb->getQuery()->getResult();
    $symfonyStyle->note('Will be created ' . count($applications) . ' messages');

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

    $symfonyStyle->success('Success! - Messages created: ' . $messages );
  }
}
