<?php


namespace AppBundle\BackOffice;


use AppBundle\Entity\Pratica;
use AppBundle\Entity\Subscriber;
use AppBundle\Entity\Subscription;
use AppBundle\Entity\SubscriptionPayment;
use DateTime;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Translation\TranslatorInterface;

class SubcriptionsBackOffice implements BackOfficeInterface
{
  const IDENTIFIER = "subscriptions";

  const NAME = 'Servizi a sottoscrizione';

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
    "related_cfs",
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

  private $allowedActivationPoints = [
    Pratica::STATUS_PAYMENT_SUCCESS,
    Pratica::STATUS_PRE_SUBMIT,
    Pratica::STATUS_SUBMITTED,
    Pratica::STATUS_REGISTERED,
    Pratica::STATUS_PENDING,
    Pratica::STATUS_COMPLETE,
    Pratica::STATUS_CANCELLED
  ];

  public function __construct(LoggerInterface $logger, TranslatorInterface $translator, EntityManager $em)
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
    if ($data instanceof Pratica && is_callable([$data, 'getDematerializedForms'])) {
      $status = $data->getStatus();
      $integrations = $data->getServizio()->getIntegrations();

      if (isset($integrations[$status]) && $integrations[$status] == get_class($this)) {
        return $this->createSubscription($data);
      }
    } else {
      // Csv import
      return $this->createSubscription($data);
    }
    return [];
  }


  public function createSubscription($data)
  {
    $originalData = is_array($data) ? $data : clone $data;
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
        $this->logger->error($this->translator->trans('backoffice.integration.fields_error'));
        return ['error' => $this->translator->trans('backoffice.integration.fields_error')];
      }
    } else {
      // CSV Import
      ksort($data);
      if (array_values(array_intersect(array_keys($data), $requiredHeaders)) != array_values($requiredHeaders)) {
        $this->logger->error($this->translator->trans('backoffice.integration.fields_error'));
        return ['error' => $this->translator->trans('backoffice.integration.fields_error')];
      }
      $fixedData = $data;
      if ($fixedData['related_cfs'] and is_string($fixedData['related_cfs'])) {
        $fixedData['related_cfs'] = explode(",", $fixedData['related_cfs']);
      } else {
        unset($fixedData['related_cfs']);
      }
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
      $this->logger->error($this->translator->trans('backoffice.integration.subscriptions.subscription_service_error', ['code'=>$fixedData['code']]));
      return ['error' => $this->translator->trans('backoffice.integration.subscriptions.subscription_service_error', ['code'=>$fixedData['code']])];
    }
    // limit of subscriptions reached
    if ($subscriptionService->getSubscribersLimit() && count($subscriptionService->getSubscriptions()) >= $subscriptionService->getSubscribersLimit()) {
      $this->logger->error($this->translator->trans('backoffice.integration.subscriptions.limit_error',
        ['user' => $fixedData['fiscal_code'], 'code'=> $fixedData['code']]));
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
      $subscription->setRelatedCFs(isset($fixedData["related_cfs"]) ? $fixedData["related_cfs"] : []);

      $this->em->persist($subscription);
      $this->em->flush();

      // update number of subscriptions
      $subscriptionService->addSubscription($subscription);
      $this->em->persist($subscriptionService);

      // Add subscription Payment

      if ($originalData instanceof Pratica && $originalData->getPaymentData()) {
        $subscriptionPayment = new SubscriptionPayment();

        if (isset($subscriptionData['payment_identifier'])) {
          $subscriptionPayment->setName($subscriptionData['payment_identifier']);
        } else {
          $subscriptionPayment->setName($this->translator->trans('iscrizioni.quota_iscrizione.nome', [
            '%subscription_name%' => strtoupper($subscriptionService->getName()),
            "%subscriber_completename%" => strtoupper($subscriber->getCompleteName()),
            "%subscriber_fiscal_code%" => strtoupper($subscriber->getFiscalCode())
          ]));
        }

        $paymentAmount = isset($originalData->getPaymentData()['payment_amount']) ? (float)$originalData->getPaymentData()['payment_amount'] : (isset($subscriptionData["payment_amount"]) ? (float)$subscriptionData["payment_amount"] : null);
        $subscriptionPayment->setDescription($this->translator->trans("iscrizioni.quota_iscrizione.descrizione"));
        $subscriptionPayment->setAmount($paymentAmount);
        $subscriptionPayment->setExternalKey($originalData->getId());
        $subscriptionPayment->setSubscription($subscription);
        if ($originalData->getPaymentType()->getIdentifier() == "mypay" and $originalData->getPaymentData()["outcome"]) {
          $subscriptionPayment->setPaymentDate((new DateTime($originalData->getPaymentData()["outcome"]["data"]["datiPagamento"]["datiSingoloPagamento"]["dataEsitoSingoloPagamento"])));
        } elseif ($originalData->getPaymentType()->getIdentifier() == "bollo") {
          $subscriptionPayment->setPaymentDate((new DateTime($originalData->getPaymentDataArray()->bollo_data_emissione)));
        }

        $this->em->persist($subscriptionPayment);
        $this->em->flush();
      }

      return $subscription;
    } catch (UniqueConstraintViolationException $exception) {
      $this->logger->error($exception->getMessage() . ' on subscription');
      return ['error' => $this->translator->trans('backoffice.integration.subscriptions.duplicate_error', [
        'user' => $subscriber->getFiscalCode(),
        'service_name' => $subscriptionService->getName()])];
    } catch (\Exception $exception) {
      $this->logger->error($exception->getMessage() . ' on subscription');
      return ['error' => $this->translator->trans('backoffice.integration.subscriptions.save_subscription_error', ['user' => $subscriber->getFiscalCode()])];
    }
  }

  public function getAllowedActivationPoints() {
    return $this->allowedActivationPoints;
  }
}
