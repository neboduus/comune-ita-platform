<?php


namespace App\Services;


use App\Entity\CPSUser;
use App\Entity\Pratica;
use App\Entity\Servizio;
use App\Entity\Subscriber;
use App\Entity\Subscription;
use App\Model\SubscriptionPayment;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Translation\TranslatorInterface;

class SubscriptionsService
{
  /**
   * @var EntityManagerInterface
   */
  private $em;
  /**
   * @var LoggerInterface
   */
  private $logger;
  /**
   * @var RouterInterface
   */
  private $router;
  private $scheme;
  private $host;
  /**
   * @var MailerService
   */
  private $mailerService;
  /**
   * @var InstanceService
   */
  private $is;
  /**
   * @var TranslatorInterface
   */
  private $translator;
  private $defaultSender;

  public function __construct(
    EntityManagerInterface $em,
    InstanceService $is,
    MailerService $mailerService,
    TranslatorInterface $translator,
    LoggerInterface $logger,
    RouterInterface $router,
    $scheme,
    $host,
    $defaultSender
  )
  {
    $this->em = $em;
    $this->is = $is;
    $this->mailerService = $mailerService;
    $this->translator = $translator;
    $this->logger = $logger;
    $this->router = $router;
    $this->scheme = $scheme;
    $this->host = $host;
    $this->defaultSender = $defaultSender;
  }

  public static function getDematerializedFormForPayment(SubscriptionPayment $paymentConfig, Subscription $subscription, $amount = null, $uniqueId = null)
  {
    $subscriptionService = $subscription->getSubscriptionService();
    $subscriber = $subscription->getSubscriber();

    $uniqueId = $uniqueId ?? trim($paymentConfig->getPaymentIdentifier() . '_' . $subscription->getSubscriptionService()->getId() . '_' . $subscription->getSubscriber()->getFiscalCode());

    $dematerializedData = [
      'subscription_service' => $subscriptionService->getId(),
      'code' => $subscriptionService->getCode(),
      'payment_amount' => $amount ?? $paymentConfig->getAmount(),
      'payment_reason' => $paymentConfig->getPaymentReason(),
      'payment_identifier' => $paymentConfig->getPaymentIdentifier(),
      'unique_id' => $uniqueId,
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

    $placeholders = [
      "%fiscal_code%" => strtoupper($subscriber->getFiscalCode()),
      "%name%" => strtoupper($subscriber->getName()),
      "%surname%" => strtoupper($subscriber->getSurname()),
      "%amount%" => $amount ?? $paymentConfig->getAmount(),
      "%payment_reason%" => $paymentConfig->getPaymentReason(),
      "%payment_identifier%" => $paymentConfig->getPaymentIdentifier(),
      "%code%" => $subscriptionService->getCode()
    ];

    return array_merge($dematerializedData, json_decode(strtr($paymentConfig->getMeta(), $placeholders), true));
  }

  public function sendEmailForDraftApplication(Pratica $pratica, Subscription $subscription)
  {
    $detailLink = $this->scheme . '://' . $this->host;
    $detailLink = $detailLink . $this->router->generate('pratica_show_detail', ['pratica' => $pratica->getId()], UrlGeneratorInterface::ABSOLUTE_PATH);

    $user = $pratica->getUser();

    $sentAmount = $this->mailerService->dispatchMail(
      $this->defaultSender,
      $this->is->getCurrentInstance()->getName(),
      $user->getEmail(),
      $user->getFullName(),
      $this->translator->trans('backoffice.integration.subscription_service.messages.new_draft', [
        "%user_name%" => $user->getFullName(),
        "%subscription_service%" => $subscription->getSubscriptionService()->getName(),
        "%service%" => $pratica->getServizio()->getName()
      ]),
      $this->translator->trans('backoffice.integration.subscription_service.messages.new_draft_subject'),
      $this->is->getCurrentInstance(),
      [
        ['label' => 'view', 'link' => $detailLink]
      ]
    );
    return $sentAmount;
  }

  public function getOrCreateUserFromSubscriber(Subscriber $subscriber) {
    if (!$subscriber->isAdult()) {
      return null;
    }

    $user = $this->em->getRepository(CPSUser::class)->findOneBy(['username' => $subscriber->getFiscalCode()]);
    if ($user) {
      return $user;
    }

    $user = new CPSUser();
    $user
      ->setUsername($subscriber->getFiscalCode())
      ->setCodiceFiscale($subscriber->getFiscalCode())
      ->setEmail($subscriber->getEmail())
      ->setEmailContatto($subscriber->getEmail())
      ->setNome($subscriber->getName())
      ->setCognome($subscriber->getSurname())
      ->setDataNascita($subscriber->getDateOfBirth())
      ->setLuogoNascita($subscriber->getPlaceOfBirth())
      ->setSdcIndirizzoResidenza($subscriber->getAddress() . ($subscriber->getHouseNumber() ? ', ' . $subscriber->getHouseNumber() : ""))
      ->setSdcCittaResidenza($subscriber->getMunicipality())
      ->setSdcCapResidenza($subscriber->getPostalCode());

    $user->addRole('ROLE_USER')
      ->addRole('ROLE_CPS_USER')
      ->setEnabled(true)
      ->setPassword('');

    $this->em->persist($user);
    return $user;
  }

  /**
   * @throws \Doctrine\DBAL\Exception
   * @throws \Doctrine\DBAL\Driver\Exception
   */
  public function getDraftsApplicationForUser(CPSUser $user, Servizio $service, $uniqueId)
  {
    $ignoreStatuses = [Pratica::STATUS_REVOKED, Pratica::STATUS_CANCELLED, Pratica::STATUS_PAYMENT_ERROR, Pratica::STATUS_WITHDRAW];
    $sql = "select id from pratica where servizio_id = '" . $service->getId() . "' and user_id = '" . $user->getId() . "' and dematerialized_forms->'data'->>'unique_id' = '" . $uniqueId . "' and pratica.status NOT IN (" . implode(',', $ignoreStatuses) . ")";
    $stmt = $this->em->getConnection()->prepare($sql);
    $result = $stmt->executeQuery();
    return $result->fetchAllAssociative();
  }

  public function getPaymentSettingIdententifiers() {
    $subscriptionServices =  $this->em->getRepository('App:SubscriptionService')->findAll();
    $subscriptionServiceIdentifiers = [];
    foreach ($subscriptionServices as $subscriptionService) {
      $subscriptionServiceIdentifiers[$subscriptionService->getCode()] = [];
      foreach ($subscriptionService->getSubscriptionPayments() as $paymentSetting) {
        $subscriptionServiceIdentifiers[$subscriptionService->getCode()][$paymentSetting->getPaymentIdentifier()] = $paymentSetting->getType();
      }
    }
    return $subscriptionServiceIdentifiers;
  }
}
