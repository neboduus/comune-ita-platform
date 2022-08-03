<?php

namespace App\Command;


use App\DataFixtures\ORM\LoadData;
use App\Entity\CPSUser;
use App\Entity\Servizio;
use App\Entity\Subscriber;
use App\Entity\Subscription;
use App\Entity\SubscriptionService;
use App\Model\SubscriptionPayment;
use App\Services\Manager\PraticaManager;
use App\Services\SubscriptionsService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;


class CreateSubscriptionPaymentDraftsCommand extends Command
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
   * @var SubscriptionsService
   */
  private $subscriptionsService;
  /**
   * @var PraticaManager
   */
  private $praticaManager;

  /**
   * @param EntityManagerInterface $entityManager
   * @param LoggerInterface $logger
   * @param SubscriptionsService $subscriptionsService
   * @param PraticaManager $praticaManager
   */
  public function __construct(EntityManagerInterface $entityManager, LoggerInterface $logger, SubscriptionsService $subscriptionsService, PraticaManager $praticaManager)
  {
    $this->entityManager = $entityManager;
    $this->logger = $logger;
    $this->subscriptionsService = $subscriptionsService;
    $this->praticaManager = $praticaManager;
    parent::__construct();
  }

  protected function configure()
  {
    $this
      ->setName('ocsdc:create_subscription_payment_drafts')
      ->setDescription('Create payment application drafts for subscription payments');
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {

    $this->logger->info('Start procedure for creating subscription payments draft applications with options: ' . \json_encode($input->getOptions()));
    $subscriptionServices = $this->entityManager->getRepository(SubscriptionService::class)->findAll();

    foreach ($subscriptionServices as $subscriptionService) {
      if (!$subscriptionService->getSubscriptionPayments()) {
        // No payments
        $this->logger->info("Subscription service " . $subscriptionService->getName() . " does not have scheduled payments");
      } else {
        foreach ($subscriptionService->getSubscriptionPayments() as $subscriptionPayment) {
          // Check payment date: create draft 7 days before expiration+
          $today = new \DateTime();
          $createDate = (clone $subscriptionPayment->getDate())->modify('-7days');

          if ($today >= $createDate && $today <= $subscriptionPayment->getDate() && $subscriptionPayment->getCreateDraft() && $subscriptionPayment->isRequired()) {
            /** @var SubscriptionPayment $subscriptionPayment */
            $service = $this->entityManager->getRepository(Servizio::class)->find($subscriptionPayment->getPaymentService());
            if (!$service) {
              $this->logger->error("Invalid payment service " . $subscriptionPayment->getPaymentService());
            } else {
              foreach ($subscriptionService->getSubscriptions() as $subscription) {
                /** @var Subscriber $subscriber */
                $subscriber = $subscription->getSubscriber();
                $users = [];
                /** @var Subscription $subscription */
                $user = $this->subscriptionsService->getOrCreateUserFromSubscriber($subscriber);
                if ($user) {
                  // Create draft for subscription owner
                  $users[] = $user;
                }
                // Create draft for subscription delegates
                foreach ($subscription->getRelatedCFs() as $relatedCF) {
                  $user = $this->entityManager->getRepository(CPSUser::class)->findOneBy(['username' => $relatedCF]);
                  if ($user) {
                    $users[] = $user;
                  }
                }
                $uniqueId = trim($subscriptionPayment->getPaymentIdentifier() . '_' . $subscription->getSubscriptionService()->getId() . '_' . $subscription->getSubscriber()->getFiscalCode());
                $dematerializedData = SubscriptionsService::getDematerializedFormForPayment($subscriptionPayment, $subscription, null, $uniqueId);

                foreach ($users as $user) {
                  // Check if application has already been created
                  $results = $this->subscriptionsService->getDraftsApplicationForUser($user, $service, $uniqueId);
                  if (!$results) {
                    // Setup preset form data
                    $application = $this->praticaManager->createDraftApplication($service, $user, $dematerializedData);
                    $this->logger->info("Payment draft application created for user " . $user->getId() . "and identifier " . $subscriptionPayment->getPaymentIdentifier());
                    $this->subscriptionsService->sendEmailForDraftApplication($application, $subscription);
                  } else {
                    $this->logger->info("Payment draft application already exists for user " . $user->getId() . "and identifier " . $subscriptionPayment->getPaymentIdentifier());
                  }
                }
              }
            }
          } else {
            // should not create
            $this->logger->info("Draft applications not needed for due date " . $subscriptionPayment->getDate()->format('d/m/Y'));
          }
        }
      }
    }
  }
}
