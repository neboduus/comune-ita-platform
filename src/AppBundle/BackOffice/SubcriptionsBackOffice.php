<?php


namespace AppBundle\BackOffice;


use AppBundle\Entity\Subscriber;
use AppBundle\Entity\Subscription;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMException;

class SubcriptionsBackOffice implements BackOfficeInterface
{
  const NAME = 'Iscrizione ai corsi';

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

  private $required_fields = array(
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
  );

  public function __construct(EntityManager $em)
  {
    $this->em = $em;
  }

  public function getName()
  {
    return self::NAME;
  }

  public function getRequiredFields()
  {
    return $this->required_fields;
  }

  public function getRequiredHeaders()
  {
    return $this->required_headers;
  }

  public function execute($subscriptionData)
  {

    $fixedData= [];
    foreach ($subscriptionData as $k => $v) {
      $keys = explode('.', $k);
      $key = end($keys);
      $fixedData[$key] = $v;
    }

    $requiredHeaders = $this->getRequiredHeaders();
    sort($requiredHeaders);
    ksort($fixedData);

    if (json_encode(array_keys($fixedData)) != json_encode($requiredHeaders)) {
      return ['error' => 'I campi richiesti non coincidono'];
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
      return ['error' => 'Non esiste un servizio con codice ' . $fixedData['code']];
    }
    // limit of subscriptions reached
    if ($subscriptionService->getSubscribersLimit() && count($subscriptionService->getSubscriptions()) >= $subscriptionService->getSubscribersLimit()) {
      return ['error' => 'Limite massimo di iscrizioni raggiunto. Utente ' . $fixedData['fiscal_code'] . ' non iscritto al corso ' . $fixedData['code']];
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
        return ['error' => 'Si è verificato un errore durante il salvataggio dell utente ' . $subscriber->getFiscalCode()];
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
      return ['error' => 'Si è verificato un errore durante il salvataggio dell iscrizione per l\'utente ' .  $subscriber->getFiscalCode()];
    }
  }
}
