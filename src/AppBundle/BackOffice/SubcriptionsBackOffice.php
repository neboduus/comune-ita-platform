<?php


namespace AppBundle\BackOffice;


use AppBundle\Entity\Pratica;
use AppBundle\Entity\Subscriber;
use AppBundle\Entity\Subscription;
use AppBundle\Entity\SubscriptionPayment;
use DateTime;
use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Translation\TranslatorInterface;

class SubcriptionsBackOffice implements BackOfficeInterface
{
  const NAME = 'Iscrizione ai corsi';

  const PATH = 'operatori_subscription-service_index';

  const APPLICANT_SUBSCRIPTION = 'applicant_subscription';
  const SUBSCRIBER_SUBSCRIPTION = 'subscriber_subscription';

  /**
   * @var LoggerInterface
   */
  private $logger;

  /**
   * @var TranslatorInterface $translator
   */
  private $translator;

  /**
   * @var EntityManager
   */
  private $em;

  private $required_headers = array(
    "name",
    "surname",
    "natoAIl",
    "place_of_birth",
    "fiscal_code",
    "address",
    "house_number",
    "municipality",
    "postal_code",
    "email_address",
    "code",
  );

  private $required_fields = [
    self::SUBSCRIBER_SUBSCRIPTION => array(
      "subscriber.data.completename.data.name",
      "subscriber.data.completename.data.surname",
      "subscriber.data.Born.data.natoAIl",
      "subscriber.data.Born.data.place_of_birth",
      "subscriber.data.fiscal_code.data.fiscal_code",
      "subscriber.data.address.data.address",
      "subscriber.data.address.data.house_number",
      "subscriber.data.address.data.municipality",
      "subscriber.data.address.data.postal_code",
      "subscriber.data.email_address",
      "code"
    ),
    self::APPLICANT_SUBSCRIPTION => array(
      "applicant.data.completename.data.name",
      "applicant.data.completename.data.surname",
      "applicant.data.Born.data.natoAIl",
      "applicant.data.Born.data.place_of_birth",
      "applicant.data.fiscal_code.data.fiscal_code",
      "applicant.data.address.data.address",
      "applicant.data.address.data.house_number",
      "applicant.data.address.data.municipality",
      "applicant.data.address.data.postal_code",
      "applicant.data.email_address",
      "code"
    )
  ];

  public function __construct(LoggerInterface $logger, TranslatorInterface $translator, EntityManager $em)
  {
    $this->logger = $logger;
    $this->translator = $translator;
    $this->em = $em;
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

  public function getRequiredHeaders()
  {
    return $this->required_headers;
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
    $originalData = clone $data;
    $requiredHeaders = $this->getRequiredHeaders();
    $requiredFields = $this->getRequiredFields();
    sort($requiredHeaders);
    if($data instanceof Pratica) {
      // Pratica: extract form data
      $data = $data->getDematerializedForms();
      unset($data['flattened']['submit']);
      $subscriptionData = $data['flattened'];
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
      if ($integrationType) {
        // Integration found: build data
        $fixedData = [];
        foreach ($requiredFields[$integrationType] as $field) {
          $keys = explode('.', $field);
          $key = end($keys);
          $fixedData[$key] = $subscriptionData[$field];
        }
        $fixedData["related_cfs"] = [];
        if ($integrationType == self::SUBSCRIBER_SUBSCRIPTION) {
          // Set applicant fiscal code as share set
          $fixedData["related_cfs"][] = $subscriptionData["applicant.data.fiscal_code.data.fiscal_code"];
        }
      } else {
        return ['error' => $this->translator->trans('backoffice.integration.fields_error')];
      }
    } else {
      // CSV Import
      ksort($data);
      if (array_values(array_intersect(array_keys($data), $requiredHeaders)) != array_values($requiredHeaders)) {
        return ['error' => $this->translator->trans('backoffice.integration.fields_error')];
      }
      $fixedData= $data;
    }

    $repo = $this->em->getRepository('AppBundle:Subscriber');
    $subscriber = $repo->findOneBy(
      array('fiscal_code' => $fixedData['fiscal_code'])
    );

    $repo = $this->em->getRepository('AppBundle:SubscriptionService');
    $subscriptionService = $repo->findOneBy(
      array('code' => $fixedData['code'])
    );

    // No such subscription service with given code
    if (!$subscriptionService) {
      return ['error' => $this->translator->trans('backoffice.integration.subscriptions.subscription_service_error', ['code'=>$fixedData['code']])];
    }
    // limit of subscriptions reached
    if ($subscriptionService->getSubscribersLimit() && count($subscriptionService->getSubscriptions()) >= $subscriptionService->getSubscribersLimit()) {
      return ['error' => $this->translator->trans('backoffice.integration.subscriptions.limit_error',
        ['user' => $fixedData['fiscal_code'], 'code'=> $fixedData['code']])];
    }

    if (!$subscriber) {
      try {
        $birthDate = \DateTime::createFromFormat('d/m/Y', $fixedData['natoAIl']);
        if (!$birthDate instanceof DateTime) {
          $birthDate = new \DateTime();
        }

        $subscriber = new Subscriber();
        $subscriber->setName($fixedData['name']);
        $subscriber->setSurname($fixedData['surname']);
        $subscriber->setDateOfBirth($birthDate);
        $subscriber->setPlaceOfBirth($fixedData['place_of_birth']);
        $subscriber->setFiscalCode($fixedData['fiscal_code']);
        $subscriber->setAddress($fixedData['address']);
        $subscriber->setHouseNumber($fixedData['house_number']);
        $subscriber->setMunicipality($fixedData['municipality']);
        $subscriber->setPostalCode($fixedData['postal_code']);
        $subscriber->setEmail($fixedData['email_address']);

        $this->em->persist($subscriber);
        $this->em->flush();
      } catch (\Exception $exception) {
        $this->logger->error($exception->getMessage() . ' on subscriber');
        return ['error' => $this->translator->trans('backoffice.integration.subscriptions.save_subscriber_error',  ['user' => $subscriber->getFiscalCode()])];
      }
    }

    try {
      $subscription = new Subscription();
      $subscription->setSubscriptionService($subscriptionService);
      $subscription->setSubscriber($subscriber);
      $subscription->setRelatedCFs($fixedData["related_cfs"]);

      $this->em->persist($subscription);
      $this->em->flush();

      // update number of subscriptions
      $subscriptionService->addSubscription($subscription);
      $this->em->persist($subscriptionService);

      // Add subscription Payment

      if ($originalData instanceof Pratica && $originalData->getPaymentData()) {
        $subscriptionPayment = new SubscriptionPayment();
        $subscriptionPayment->setName($this->translator->trans('iscrizioni.quota_iscrizione.nome', [
          '%subscription_name%' => strtoupper($subscriptionService->getName()),
          "%subscriber_completename%" => strtoupper($subscriber->getCompleteName()),
          "%subscriber_fiscal_code%" => strtoupper($subscriber->getFiscalCode())
        ]));
        $subscriptionPayment->setDescription($this->translator->trans("iscrizioni.quota_iscrizione.descrizione"));
        $subscriptionPayment->setAmount((float)$originalData->getPaymentData()['payment_amount']);
        $subscriptionPayment->setExternalKey($originalData->getId());
        $subscriptionPayment->setSubscription($subscription);
        if ($originalData->getPaymentType()->getName() == "MyPay" and $originalData->getPaymentData()["outcome"]) {
          $subscriptionPayment->setPaymentDate((new DateTime($originalData->getPaymentData()["outcome"]["data"]["datiPagamento"]["datiSingoloPagamento"]["dataEsitoSingoloPagamento"])));
        }

        $this->em->persist($subscriptionPayment);
        $this->em->flush();
      }

      return $subscription;
    } catch (\Exception $exception) {
      $this->logger->error($exception->getMessage() . ' on subscription');
      return ['error' => $this->translator->trans('backoffice.integration.subscriptions.save_subscription_error', ['user' => $subscriber->getFiscalCode()])];
    }
  }
}
