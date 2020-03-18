<?php


namespace AppBundle\BackOffice;


use AppBundle\Entity\Pratica;
use AppBundle\Entity\Subscriber;
use AppBundle\Entity\Subscription;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Translation\TranslatorInterface;

class SubcriptionsBackOffice implements BackOfficeInterface
{
  const NAME = 'Iscrizione ai corsi';

  const PATH = 'operatori_subscription-service_index';

  private $em;

  /**
   * @var TranslatorInterface $translator
   */
  private $translator;

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
    'subscriber_subscription' => array(
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
    'applicant_subscription' => array(
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

  public function __construct(TranslatorInterface $translator, EntityManager $em)
  {
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
        $birthDate = new \DateTime($fixedData['natoAIl']);
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
        return ['error' => $this->translator->trans('backoffice.integration.subscriptions.save_subscriber_error',  ['user' => $subscriber->getFiscalCode()])];
      }
    }

    try {
      $subscription = new Subscription();
      $subscription->setSubscriptionService($subscriptionService);
      $subscription->setSubscriber($subscriber);

      $this->em->persist($subscription);
      $this->em->flush();

      // update number of subscriptions
      $subscriptionService->addSubscription($subscription);
      $this->em->persist($subscriptionService);
      $this->em->persist($subscriptionService);

      return $subscription;
    } catch (\Exception $exception) {
      return ['error' => $this->translator->trans('backoffice.integration.subscriptions.save_subscription_error', ['user' => $subscriber->getFiscalCode()])];
    }
  }
}
