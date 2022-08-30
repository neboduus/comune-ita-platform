<?php

namespace App\Command;

use App\Entity\SubscriptionService;
use App\Model\SubscriptionPayment;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;


class HotFixMigratePaymentTypeCommand extends Command
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
      ->setName('ocsdc:hotfix-migrate-payment-type')
      ->setDescription("Command for the migration of the payment setting's type of a subscription service");
  }


  protected function execute(InputInterface $input, OutputInterface $output)
  {

    $io = new SymfonyStyle($input, $output);

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
        $this->entityManager->persist($subscriptionService);
        $this->entityManager->flush();
        $io->success('Migrated payment settings for subscription service ' . $subscriptionService->getName());
        return 0;
      } catch (ORMException $e) {
        $io->error('Failed to migrate payment settings for subscription service ' . $subscriptionService->getName());
        return 1;
      }
    }
  }


  /**
   * @return SubscriptionService[]
   */
  private function getSubscriptionServices()
  {
    $repo = $this->entityManager->getRepository('App\Entity\SubscriptionService');

    return $repo->findAll();
  }
}
