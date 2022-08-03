<?php

namespace App\Command;

use App\Entity\SubscriptionService;
use App\Model\SubscriptionPayment;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;


class HotFixMigratePaymentTypeCommand extends Command
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
      ->setName('ocsdc:hotfix-migrate-payment-type')
      ->setDescription("Command for the migration of the payment setting's type of a subscription service");
  }


  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $this->em = $this->getContainer()->get('doctrine')->getManager();
    $this->io = new SymfonyStyle($input, $output);

    foreach ($this->getSubscriptionServices() as $subscriptionService) {
      $migratedPaymentSettings = [];
      foreach ($subscriptionService->getSubscriptionPayments() as $paymentSetting) {
        if ($paymentSetting->isSubscriptionFee()) {
          $paymentSetting->setType(SubscriptionPayment::TYPE_SUBSCRIPTION_FEE);
        } elseif ($paymentSetting->isRequired()) {
          $paymentSetting->setType(SubscriptionPayment::TYPE_ADDITIONAL_FEE);
        } else {
          $paymentSetting->setType(SubscriptionPayment::TYPE_OPTIONAL);
        }
        $migratedPaymentSettings[] = $paymentSetting;
      }
      $subscriptionService->setSubscriptionPayments($migratedPaymentSettings);

      try {
        $this->em->persist($subscriptionService);
        $this->em->flush();
        $output->writeln('Migrated payment settings for subscription service ' . $subscriptionService->getName());
      } catch (ORMException $e) {
        $output->writeln('Failed to migrate payment settings for subscription service ' . $subscriptionService->getName());
      }
    }
  }


  /**
   * @return SubscriptionService[]
   */
  private function getSubscriptionServices()
  {
    $repo = $this->em->getRepository('App\Entity\SubscriptionService');

    return $repo->findAll();
  }
}
