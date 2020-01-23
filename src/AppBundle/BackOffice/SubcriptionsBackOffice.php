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
    "date_of_birth",
    "place_of_birth",
    "fiscal_code",
    "address",
    "house_number",
    "municipality",
    "postal_code",
    "email",
    "code"
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

    if (json_encode(array_keys($subscriptionData)) != json_encode($this->getRequiredHeaders()) &&
      json_encode(array_keys($subscriptionData)) != json_encode($this->getRequiredFields())) {
      return null;
    }

    $repo = $this->em->getRepository('AppBundle:Subscriber');
    $subscriber = $repo->findOneBy(
      array('fiscal_code' => $subscriptionData['fiscal_code'])
    );

    $repo = $this->em->getRepository('AppBundle:SubscriptionService');
    $subscriptionService = $repo->findOneBy(
      array('code' => $subscriptionData['code'])
    );

    // No such subscription service with given code
    if (!$subscriptionService) {
      // todo: add logger
      return null;
    }
    // limit of subscriptions reached
    if ($subscriptionService->getSubscribersLimit() && count($subscriptionService->getSubscriptions()) >= $subscriptionService->getSubscribersLimit()) {
      // todo: add logger
      return null;
    }

    if (!$subscriber) {
      try {
        $birthDate = new \DateTime($subscriptionData['date_of_birth']);
        if (!$birthDate instanceof DateTime) {
          $birthDate = new \DateTime();
        }

        $subscriber = new Subscriber();
        $subscriber->setName($subscriptionData['name']);
        $subscriber->setSurname($subscriptionData['surname']);
        $subscriber->setDateOfBirth($birthDate);
        $subscriber->setPlaceOfBirth($subscriptionData['place_of_birth']);
        $subscriber->setFiscalCode($subscriptionData['fiscal_code']);
        $subscriber->setAddress($subscriptionData['address']);
        $subscriber->setHouseNumber($subscriptionData['house_number']);
        $subscriber->setMunicipality($subscriptionData['municipality']);
        $subscriber->setPostalCode($subscriptionData['postal_code']);
        $subscriber->setEmail($subscriptionData['email']);

        $this->em->persist($subscriber);
        $this->em->flush();
      } catch (\Exception $exception) {
        // todo: add logger
        return null;
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
      // todo: add logger
      return null;
    }
  }
}
