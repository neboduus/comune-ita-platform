<?php

namespace App\Command;

use Doctrine\ORM\EntityManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use CodiceFiscale\InverseCalculator;

class HotFixSubscriberBirthDateFromFiscalCodeCommand extends Command
{

  protected function configure()
  {
    $this
      ->setName('ocsdc:subscriber:birthdate-from-fiscalcode')
      ->setDescription('Ricava la data di nascita dal codice fiscale e la salva per gli iscritti')
      ->addOption('run', null, InputOption::VALUE_NONE, 'Run');
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {

    $io = new SymfonyStyle($input, $output);
    try {

      $run = $input->getOption('run');

      /** @var EntityManager $entityManager */
      $entityManager = $this->getContainer()->get('doctrine')->getManager();
      $subscriberRepo = $entityManager->getRepository('App\Entity\Subscriber');
      $subscribers = $subscriberRepo->findAll();

      $subscribersToFix = $subscribersFixed = 0;

      foreach ($subscribers as $s) {

        $inverseCalculator = new InverseCalculator($s->getFiscalCode());
        $subject = $inverseCalculator->getSubject();

        if ($s->getDateOfBirth()->format('Y-m-d') != $subject->getBirthDate()->format('Y-m-d')) {
          $subscribersToFix++;
          if ($run) {
            $s->setDateOfBirth($subject->getBirthDate());
            $entityManager->persist($s);
            $subscribersFixed++;
          }
        }
      }

      if ($run) {
        $entityManager->flush();
      }

      $io->success(
        sprintf(
          'Fetched %s subscribers to fix, %s fixed.',
          $subscribersToFix,
          $subscribersFixed
        )
      );

      return 0;

    } catch (\Exception $e) {
      $io->error('Error: '.$e->getMessage());

      return 1;
    }
  }
}
