<?php


namespace AppBundle\BackOffice;


use AppBundle\Entity\Pratica;
use AppBundle\Entity\Subscription;
use AppBundle\Entity\SubscriptionPayment;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Translation\TranslatorInterface;

class SubcriptionPaymentsBackOffice implements BackOfficeInterface
{
  const IDENTIFIER = "subscription_payments";

  const NAME = 'Pagamenti per servizi a sottoscrizione';

  const PATH = 'operatori_subscription-service_payments_index';

  const APPLICANT_SUBSCRIPTION_PAYMENT_BY_CODE = 'applicant_subscription_payment_by_code';
  const SUBSCRIBER_SUBSCRIPTION_PAYMENT_BY_CODE = 'subscriber_subscription_payment_by_code';
  const APPLICANT_SUBSCRIPTION_PAYMENT_BY_ID = 'applicant_subscription_payment_by_id';
  const SUBSCRIBER_SUBSCRIPTION_PAYMENT_BY_ID = 'subscriber_subscription_payment_by_id';

  /**
   * @var LoggerInterface
   */
  private $logger;

  /**
   * @var TranslatorInterface $translator
   */
  private $translator;

  /**
   * @var EntityManagerInterface
   */
  private $em;


  private $required_fields = [
    self::SUBSCRIBER_SUBSCRIPTION_PAYMENT_BY_CODE=> array(
      "subscriber.data.fiscal_code.data.fiscal_code",
      "code",
      "payment_identifier",
      "payment_amount",
      "payment_reason",
      "unique_id"
    ),
    self::APPLICANT_SUBSCRIPTION_PAYMENT_BY_CODE => array(
      "applicant.data.fiscal_code.data.fiscal_code",
      "code",
      "payment_identifier",
      "payment_amount",
      "payment_reason",
      "unique_id"
    ),
     self::SUBSCRIBER_SUBSCRIPTION_PAYMENT_BY_ID=> array(
      "subscriber.data.fiscal_code.data.fiscal_code",
      "subscription_service",
      "payment_identifier",
      "payment_amount",
      "payment_reason",
      "unique_id"
    ),
    self::APPLICANT_SUBSCRIPTION_PAYMENT_BY_ID => array(
      "applicant.data.fiscal_code.data.fiscal_code",
      "subscription_service",
      "payment_identifier",
      "payment_amount",
      "payment_reason",
      "unique_id"
    )
  ];

  private $allowedActivationPoints = [
    Pratica::STATUS_PAYMENT_SUCCESS,
    Pratica::STATUS_SUBMITTED,
    Pratica::STATUS_REGISTERED,
    Pratica::STATUS_PENDING,
    Pratica::STATUS_COMPLETE
  ];

  public function __construct(LoggerInterface $logger, TranslatorInterface $translator, EntityManagerInterface $em)
  {
    $this->logger = $logger;
    $this->translator = $translator;
    $this->em = $em;
  }

  public function getIdentifier()
  {
    return self::IDENTIFIER;
  }

  public function getName()
  {
    return self::NAME;
  }

  public function getPath()
  {
    return self::PATH;
  }

  public function getRequiredFields()
  {
    return $this->required_fields;
  }

  public function checkRequiredFields($schema)
  {
    $errors = [];
    foreach ($this->getRequiredFields() as $key => $requiredFields) {
      foreach ($requiredFields as $field) {
        if (!array_key_exists($field . '.label', $schema)) {
          $errors[$key][] = $this->translator->trans('backoffice.integration.missing_field', ['field' => $field]);
        }
      }
      if (!array_key_exists($key, $errors)) {
        return null;
      }
    }
   return $errors;
  }

  public function execute($data)
  {
    if ($data instanceof Pratica && is_callable([$data, 'getDematerializedForms'])) {
      $status = $data->getStatus();
      $integrations = $data->getServizio()->getIntegrations();

      if (isset($integrations[$status]) && $integrations[$status] == get_class($this)) {
        return $this->createSubscriptionPayment($data);
      }
    }
    return [];
  }


  public function createSubscriptionPayment($data)
  {
    $requiredFields = $this->getRequiredFields();
    if($data instanceof Pratica) {
      // Pratica: extract form data
      $paymentData = $data->getDematerializedForms();
      $subscriptionData = $paymentData['flattened'];
      ksort($subscriptionData);

      // Check among all possible integrations which one to use
      $integrationType = null;
      foreach ($requiredFields as $type => $fields) {
        sort($fields);
        if(! $integrationType && array_values(array_intersect(array_keys($subscriptionData), array_values($fields))) == array_values($fields)) {
          // Integration type found: no previous integration found
          $integrationType=$type;
        }
      }
      if (!$integrationType) {
        $this->logger->error($this->translator->trans('backoffice.integration.fields_error'));
        return ['error' => $this->translator->trans('backoffice.integration.fields_error')];
      }
    }

    $subscriberFiscalCode = (in_array($integrationType, [self::SUBSCRIBER_SUBSCRIPTION_PAYMENT_BY_CODE, self::SUBSCRIBER_SUBSCRIPTION_PAYMENT_BY_ID]) ) ?
      $subscriptionData['subscriber.data.fiscal_code.data.fiscal_code'] : $subscriptionData['applicant.data.fiscal_code.data.fiscal_code'];


    $qb = $this->em->createQueryBuilder()
      ->select('subscription')
      ->from(Subscription::class, 'subscription')
      ->join('subscription.subscriber', 'subscriber')
      ->join('subscription.subscription_service', 'subscriptionService')
      ->where('lower(subscriber.fiscal_code) = :fiscal_code')
      ->setParameter('fiscal_code', strtolower($subscriberFiscalCode));

    if (in_array($integrationType, [self::APPLICANT_SUBSCRIPTION_PAYMENT_BY_CODE, self::SUBSCRIBER_SUBSCRIPTION_PAYMENT_BY_CODE])) {
      $qb
        ->andWhere('subscriptionService.code = :code')
        ->setParameter('code', $subscriptionData['code']);
    }
    if (in_array($integrationType, [self::APPLICANT_SUBSCRIPTION_PAYMENT_BY_ID, self::SUBSCRIBER_SUBSCRIPTION_PAYMENT_BY_ID])) {
      $qb
        ->andWhere('subscriptionService.id = :subscriptionServiceId')
        ->setParameter('subscriptionServiceId', $subscriptionData['subscription_service']);
    }

    try {
      $subscription = $qb->getQuery()->getSingleResult();
    } catch (\Exception $e) {
      $this->logger->error($this->translator->trans('backoffice.integration.subscriptions.subscription_error', [
        '%code%'=> $subscriptionData['code'] ?? $subscriptionData['subscription_service'],
        '%fiscal_code%' => $subscriberFiscalCode
      ]));
      return ['error' => $this->translator->trans('backoffice.integration.subscriptions.subscription_error', [
        '%code%'=> $subscriptionData['code'] ?? $subscriptionData['subscription_service'],
        '%fiscal_code%' => $subscriberFiscalCode
      ])];
    }

    try {
      // Add subscription Payment
      $subscriptionPayment = new SubscriptionPayment();
      $subscriptionPayment->setName($subscriptionData["payment_identifier"]);
      $subscriptionPayment->setDescription($subscriptionData["payment_reason"]);
      $subscriptionPayment->setAmount((float)$subscriptionData['payment_amount']);
      $subscriptionPayment->setExternalKey($data->getId());
      $subscriptionPayment->setSubscription($subscription);
      if ($data->getPaymentType() == "mypay" and $data->getPaymentData()["outcome"]) {
        $subscriptionPayment->setPaymentDate((new DateTime($data->getPaymentData()["outcome"]["data"]["datiPagamento"]["datiSingoloPagamento"]["dataEsitoSingoloPagamento"])));
      } elseif ($data->getPaymentType() == "bollo") {
        $subscriptionPayment->setPaymentDate((new DateTime(json_decode($data->getPaymentData(), true)["bollo_data_emissione"])));
      }

      $this->em->persist($subscriptionPayment);
      $this->em->flush();

      return $subscriptionPayment;
    } catch (\Exception $exception) {

      $this->logger->error($exception->getMessage() . ' on subscription');
      return ['error' => $this->translator->trans('backoffice.integration.subscriptions.save_subscription_error', [
        'user' => $subscription->getSubscriber()->getFiscalCode()])
      ];
    }
  }

  public function getAllowedActivationPoints() {
    return $this->allowedActivationPoints;
  }
}
