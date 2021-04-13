<?php

namespace AppBundle\Command;


use AppBundle\Entity\CPSUser;
use AppBundle\Entity\Servizio;
use AppBundle\Entity\Subscriber;
use AppBundle\Entity\Subscription;
use AppBundle\Entity\SubscriptionService;
use AppBundle\Model\SubscriptionPayment;
use AppBundle\Services\MailerService;
use AppBundle\Services\Manager\PraticaManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;


class CreateSubscriptionPaymentDraftsCommand extends ContainerAwareCommand
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
    $is = $this->getContainer()->get('ocsdc.instance_service');

    /** @var PraticaManager $praticaManager */
    $praticaManager = $this->getContainer()->get('ocsdc.pratica_manager');

    /** @var MailerService $mailerService */
    $mailerService = $this->getContainer()->get('ocsdc.mailer');
    $defaultSender = $this->getContainer()->getParameter('default_from_email_address');

    $translator = $this->getContainer()->get('translator');
    $router = $this->getContainer()->get('router');

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

          if ($today >= $createDate && $today <= $subscriptionPayment->getDate()) {
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
                $user = $em->getRepository(CPSUser::class)->findOneBy(['username' => $subscriber->getFiscalCode()]);
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
                foreach ($users as $user) {
                  $unique_id = trim($subscriptionPayment->getPaymentIdentifier() . '_' . $subscription->getSubscriptionService()->getId() . '_' . $subscription->getSubscriber()->getFiscalCode());
                  // Check if application has already been created
                  $sql = "select id from pratica where servizio_id = '" . $service->getId() . "' and user_id = '" . $user->getId() . "' and dematerialized_forms->'data'->>'unique_id' = '" . $unique_id . "'";
                  $stmt = $em->getConnection()->prepare($sql);
                  $stmt->execute();
                  $results = $stmt->fetchAll();

                  if (!$results) {
                    // Setup preset form data
                    $dematerializedData = [
                      'subscription_service' => $subscriptionService->getId(),
                      'code' => $subscriptionService->getCode(),
                      'payment_amount' => $subscriptionPayment->getAmount(),
                      'payment_reason' => $subscriptionPayment->getPaymentReason(),
                      'payment_identifier' => $subscriptionPayment->getPaymentIdentifier(),
                      'unique_id' => $unique_id,
                      'subscriber' => [
                        'data' => [
                          'completename' => [
                            'data' => [
                              'name' => $subscriber->getName(),
                              'surname' => $subscriber->getSurname()
                            ]
                          ],
                          'Born' => [
                            'data' => [
                              'natoAIl' => $subscriber->getDateOfBirth()->format('d/m/Y'),
                              'place_of_birth' => $subscriber->getPlaceOfBirth()
                            ]
                          ],
                          'fiscal_code' => [
                            'data' => [
                              'fiscal_code' => $subscriber->getFiscalCode(),
                            ]
                          ],
                          'address' => [
                            'data' => [
                              'address' => $subscriber->getAddress(),
                              'house_number' => $subscriber->getHouseNumber(),
                              'municipality' => $subscriber->getMunicipality(),
                              'postal_code' => $subscriber->getPostalCode(),
                            ]
                          ],
                          'email_address' => $subscriber->getEmail()
                        ],
                      ]
                    ];
                    $dematerializedData = array_merge($dematerializedData, json_decode($subscriptionPayment->getMeta(), true));
                    $pratica = $praticaManager->createDraftApplication($service, $user, $dematerializedData);
                    $logger->info("Payment draft application created for user " . $user->getId() . "and identifier " . $subscriptionPayment->getPaymentIdentifier());

                    $detailLink = $this->getContainer()->getParameter('ocsdc_scheme') . '://' . $this->getContainer()->getParameter('ocsdc_host') . '/';
                    $detailLink = $detailLink . $router->generate('pratica_show_detail', ['pratica' => $pratica->getId()], UrlGeneratorInterface::RELATIVE_PATH);

                    $sentAmount = $mailerService->dispatchMail(
                      $defaultSender,
                      $is->getCurrentInstance()->getName(),
                      $user->getEmail(),
                      $user->getFullName(),
                      $translator->trans('backoffice.integration.subscription_service.messages.new_draft', [
                        "%user_name%" => $user->getFullName(),
                        "%subscription_service%" => $subscriptionService->getName(),
                        "%service%" => $service->getName()
                      ]),
                      $translator->trans('backoffice.integration.subscription_service.messages.new_draft_subject'),
                      $is->getCurrentInstance(),
                      [
                        ['label' => 'view', 'link' => $detailLink]
                      ]
                    );
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
