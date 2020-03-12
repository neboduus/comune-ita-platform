<?php


namespace AppBundle\BackOffice;


use AppBundle\Entity\Meeting;
use Doctrine\ORM\EntityManager;

class CalendarsBackOffice implements BackOfficeInterface
{
  const NAME = 'Prenotazione appuntamenti';

  const PATH = 'operatori_calendars_index';

  private $em;

  private $required_fields = array(
    "applicant.data.completename.data.name",
    "applicant.data.completename.data.surname",
    "user_message",
    "calendar"
  );

  public function __construct(EntityManager $em)
  {
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

  public function execute($pratica)
  {
    $data = $pratica->getDematerializedForms();
    unset($data['flattened']['submit']);
    $meetingData = $data['flattened'];

    $requiredFields = $this->getRequiredFields();
    sort($requiredFields);
    ksort($meetingData);

    // Check required field
    if (array_values(array_intersect(array_keys($meetingData), $requiredFields)) != array_values($requiredFields)) {
      return ['error' => 'I campi richiesti non coincidono'];
    }
    // Check contacts. At least one among tel, cell, email is required
    if (!$meetingData['applicant.data.email_address'] &&
      !$meetingData['applicant.data.phone_number']) {
      return ['error' => "E' necessario fornire almeno un recapito"];
    }
    preg_match_all("/\(([^\)]*)\)/", $meetingData['calendar'], $matches);

    $repo = $this->em->getRepository('AppBundle:Calendar');
    $calendar = $repo->findOneBy(['id' => $matches[1]]);
    if (!$calendar) {
      return ['error' => 'Id del calendario mancante o non corretto'];
    }
    // Get start-end datetime
    $tmp = explode('@', $meetingData['calendar']);
    $date = trim($tmp[0]);
    $time = $tmp[1];
    $time = trim(explode('(', $time)[0]);
    $tmp = explode('-', $time);
    $startTime = trim($tmp[0]);
    $endTime = trim($tmp[1]);
    $start = \DateTime::createFromFormat('d/m/Y:H:i', $date . ':' . $startTime);
    $end = \DateTime::createFromFormat('d/m/Y:H:i', $date . ':' . $endTime);

    try {
      $meeting = new Meeting();
      $meeting->setCalendar($calendar);
      $meeting->setEmail($meetingData['applicant.data.email_address']);
      $meeting->setName($pratica->getUser()->getFullName());
      $meeting->setPhoneNumber($meetingData['applicant.data.phone_number']);
      $meeting->setFiscalCode($pratica->getUser()->getCodiceFiscale());
      $meeting->setUser($pratica->getUser());
      $meeting->setUserMessage($meetingData['user_message']);
      $meeting->setFromTime($start);
      $meeting->setToTime($end);

      $this->em->persist($meeting);
      $this->em->flush($meeting);

      return $meeting;
    } catch (\Exception $exception) {
      return ['error' => 'Si è verificato un errore durante il salvataggio della prenotazione'];
    }
  }
}
