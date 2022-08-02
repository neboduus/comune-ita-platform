<?php

namespace App\Command;


use App\Entity\CPSUser;
use App\Entity\Servizio;
use App\Entity\Subscriber;
use App\Entity\Subscription;
use App\Entity\SubscriptionService;
use App\Model\SubscriptionPayment;
use App\Services\Manager\PraticaManager;
use App\Services\SubscriptionsService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


class CreateSubscriptionPaymentDraftsCommand extends Command
{

  protected function configure()
  {
    $this
      ->setName('ocsdc:create_subscription_payment_drafts')
      ->setDescription('Create payment application drafts for subscription payments');
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $logger = $this->getContainer()->get('logger');
    $logger->info('Start procedure for creating subscription payments draft applications with options: ' . \json_encode($input->getOptions()));

    $em = $this->getContainer()->get('doctrine')->getManager();

    $subscriptionsService = $this->getContainer()->get('ocsdc.subscriptions_service');

    /** @var PraticaManager $praticaManager */
    $praticaManager = $this->getContainer()->get('ocsdc.pratica_manager');

    $subscriptionServices = $em->getRepository(SubscriptionService::class)->findAll();

    foreach ($subscriptionServices as $subscriptionService) {
      if (!$subscriptionService->getSubscriptionPayments()) {
        // No payments
        $logger->info("Subscription service " . $subscriptionService->getName() . " does not have scheduled payments");
      } else {
        foreach ($subscriptionService->getSubscriptionPayments() as $subscriptionPayment) {
          // Check payment date: create draft 7 days before expiration+
          $today = new \DateTime();
          $createDate = (clone $subscriptionPayment->getDate())->modify('-7days');

          if ($today >= $createDate && $today <= $subscriptionPayment->getDate() && $subscriptionPayment->getCreateDraft() && $subscriptionPayment->isRequired()) {
            /** @var SubscriptionPayment $subscriptionPayment */
            $service = $em->getRepository(Servizio::class)->find($subscriptionPayment->getPaymentService());
            if (!$service) {
              $logger->error("Invalid payment service " . $subscriptionPayment->getPaymentService());
            } else {
              foreach ($subscriptionService->getSubscriptions() as $subscription) {
                /** @var Subscriber $subscriber */
                $subscriber = $subscription->getSubscriber();
                $users = [];
                /** @var Subscription $subscription */
                $user = $subscriptionsService->getOrCreateUserFromSubscriber($subscriber);
                if ($user) {
                  // Create draft for subscription owner
                  $users[] = $user;
                }
                // Create draft for subscription delegates
                foreach ($subscription->getRelatedCFs() as $relatedCF) {
                  $user = $em->getRepository(CPSUser::class)->findOneBy(['username' => $relatedCF]);
                  if ($user) {
                    $users[] = $user;
                  }
                }
                $uniqueId = trim($subscriptionPayment->getPaymentIdentifier() . '_' . $subscription->getSubscriptionService()->getId() . '_' . $subscription->getSubscriber()->getFiscalCode());
                $dematerializedData = SubscriptionsService::getDematerializedFormForPayment($subscriptionPayment, $subscription, null, $uniqueId);

                foreach ($users as $user) {
                  // Check if application has already been created
                  $results = $subscriptionsService->getDraftsApplicationForUser($user, $service, $uniqueId);
                  if (!$results) {
                    // Setup preset form data
                    $application = $praticaManager->createDraftApplication($service, $user, $dematerializedData);
                    $logger->info("Payment draft application created for user " . $user->getId() . "and identifier " . $subscriptionPayment->getPaymentIdentifier());
                    $subscriptionsService->sendEmailForDraftApplication($application, $subscription);
                  } else {
                    $logger->info("Payment draft application already exists for user " . $user->getId() . "and identifier " . $subscriptionPayment->getPaymentIdentifier());
                  }
                }
              }
            }
          } else {
            // should not create
            $logger->info("Draft applications not needed for due date " . $subscriptionPayment->getDate()->format('d/m/Y'));
          }
        }
      }
    }
  }
}
