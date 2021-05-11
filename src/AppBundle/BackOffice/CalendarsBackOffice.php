<?php


namespace AppBundle\BackOffice;


use AppBundle\Entity\CPSUser;
use AppBundle\Entity\Meeting;
use AppBundle\Entity\Pratica;
use AppBundle\Services\InstanceService;
use AppBundle\Services\MeetingService;
use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Translation\TranslatorInterface;

class CalendarsBackOffice implements BackOfficeInterface
{
  const IDENTIFIER = 'calendars';

  const NAME = 'Prenotazione appuntamenti';

  const PATH = 'operatori_calendars_index';

  /**
   * @var EntityManager
   */
  private $em;
  /**
   * @var InstanceService
   */
  private $is;

  /**
   * @var MeetingService
   */
  private $meetingService;

  /**
   * @var TranslatorInterface $translator
   */
  private $translator;

  private $required_fields = [
    'applicant_meeting' => array(
      "applicant.data.completename.data.name",
      "applicant.data.completename.data.surname",
      "calendar"
    )
  ];

  private $allowedActivationPoints = [
    Pratica::STATUS_PRE_SUBMIT,
    Pratica::STATUS_SUBMITTED,
    Pratica::STATUS_PAYMENT_SUCCESS
  ];

  /**
   * @var LoggerInterface
   */
  private $logger;

  public function __construct(EntityManager $em, InstanceService $is, MeetingService $meetingService, TranslatorInterface $translator, LoggerInterface $logger)
  {
    $this->translator = $translator;
    $this->meetingService = $meetingService;
    $this->em = $em;
    $this->is = $is;
    $this->logger = $logger;
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

  public function execute($data)
  {
    if ($data instanceof Pratica && is_callable([$data, 'getDematerializedForms'])) {
      $status = $data->getStatus();
      $integrations = $data->getServizio()->getIntegrations();

      if (isset($integrations[$status]) && $integrations[$status] == get_class($this)) {
        return $this->createMeetingFromPratica($data);
      } else {
        // Extract meeting id from calendar string
        preg_match_all("/\(([^\)]*)\)/", $data->getDematerializedForms()['flattened']['calendar'], $matches);
        $meetingId = trim(explode("#", $matches[1][0])[1]);
        $meeting = $meetingId ? $this->em->getRepository('AppBundle:Meeting')->find($meetingId) : null;

        if (!$meeting) {
          // Meeting not found, can't update
          return [];
        }

        switch ($status) {
          case Pratica::STATUS_WITHDRAW:
            // Cancel meeting
            $meeting->setStatus(Meeting::STATUS_CANCELLED);
            break;
          case Pratica::STATUS_PAYMENT_PENDING:
            // Increment draft duration
            $currentExpiration = clone $meeting->getDraftExpiration() ?? new \DateTime();
            $meeting->setDraftExpiration($currentExpiration->modify("+ {$meeting->getCalendar()->getDraftsDurationIncrement()} seconds"));
          break;
          case Pratica::STATUS_PAYMENT_ERROR:
          case Pratica::STATUS_CANCELLED:
            // Refuse meeting
            $meeting->setStatus(Meeting::STATUS_REFUSED);
            break;
          default:
            // do nothing
        }
        try {
          $this->em->persist($meeting);
          $this->em->flush();
          return $meeting;
        } catch (\Exception $e) {
          $this->logger->error($this->translator->trans('backoffice.integration.calendars.save_meeting_error'));
          return ['error' => $this->translator->trans('backoffice.integration.calendars.save_meeting_error')];
        }
      }
    }
    return [];
  }

  public function checkRequiredFields($schema): ?array
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

  public function getAllowedActivationPoints()
  {
    return $this->allowedActivationPoints;
  }

  private function createMeetingFromPratica(Pratica $pratica)
  {
    $data = $pratica->getDematerializedForms();
    unset($data['flattened']['submit']);
    $submission = $data['flattened'];
    ksort($submission);

    // Check among all possible integrations which one to use
    $integrationType = $this->getIntegrationType($submission);

    if (!$integrationType) {
      // Meeting data is not suitable for integration
      $this->logger->error($this->translator->trans('backoffice.integration.fields_error'));
      return ['error' => $this->translator->trans('backoffice.integration.fields_error')];
    }

    $meetingData = $this->getMeetingData($submission, $pratica->getUser());

    // Check contacts. At least one among tel and email is required
    if (!($meetingData['phone_number'] || $meetingData['email'])) {
      $this->logger->error($this->translator->trans('backoffice.integration.calendars.missing_contacts'));
      return ['error' => $this->translator->trans('backoffice.integration.calendars.missing_contacts')];
    }


    $calendar = $meetingData['calendar'] ? $this->em->getRepository('AppBundle:Calendar')->find($meetingData['calendar']) : null;
    if (!$calendar) {
      $this->logger->error($this->translator->trans(
        'backoffice.integration.calendars.calendar_error',
        ['calendar_id' => $meetingData['calendar']]
      ));
      return ['error' => $this->translator->trans(
        'backoffice.integration.calendars.calendar_error',
        ['calendar_id' => $meetingData['calendar']]
      )];
    }

    try {
      $meeting = $meetingData['meeting_id'] ? $this->em->getRepository('AppBundle:Meeting')->find($meetingData['meeting_id']) : null;
      if (!$meeting) {
        $meeting = new Meeting();
      }

      $meeting->setEmail($meetingData['email']);
      $meeting->setName($meetingData['name']);
      $meeting->setPhoneNumber($meetingData['phone_number']);
      $meeting->setFiscalCode($meetingData['fiscal_code']);
      $meeting->setUser($meetingData['user']);
      $meeting->setUserMessage(isset($meetingData['user_message']) ? $meetingData['user_message'] : null);
      $meeting->setFromTime($meetingData['from_time']);
      $meeting->setToTime($meetingData['to_time']);
      $meeting->setCalendar($calendar);

      if (!$this->meetingService->isSlotAvailable($meeting) || !$this->meetingService->isSlotValid($meeting)) {
        // Send email
        $this->meetingService->sendEmailUnavailableMeeting($meeting);
        $this->logger->error($this->translator->trans('backoffice.integration.calendars.invalid_slot'));
        return ['error' => $this->translator->trans('backoffice.integration.calendars.invalid_slot')];
      }

      $this->em->persist($meeting);
      $this->em->flush();

      return $meeting;
    } catch (\Exception $exception) {
      dump($exception); exit();
      $this->logger->error($this->translator->trans('backoffice.integration.calendars.save_meeting_error'));
      return ['error' => $this->translator->trans('backoffice.integration.calendars.save_meeting_error')];
    }
  }

  private function getIntegrationType($data): ?string
  {
    $integrationType = null;
    foreach ($this->getRequiredFields() as $type => $fields) {
      sort($fields);
      if (!$integrationType && array_values(array_intersect(array_keys($data), array_values($fields))) == array_values($fields)) {
        // Integration type found: no previous integration found
        $integrationType = $type;
      }
    }
    return $integrationType;
  }

  private function getMeetingData($submission, CPSUser $user)
  {
    // Get start-end datetime
    $tmp = explode('@', $submission['calendar']);
    $date = trim($tmp[0]);
    $time = $tmp[1];
    $time = trim(explode('(', $time)[0]);
    $tmp = explode('-', $time);
    $startTime = trim($tmp[0]);
    $endTime = trim($tmp[1]);
    $start = \DateTime::createFromFormat('d/m/Y:H:i', $date . ':' . $startTime);
    $end = \DateTime::createFromFormat('d/m/Y:H:i', $date . ':' . $endTime);

    // Extract meeting id from calendar string
    preg_match_all("/\(([^\)]*)\)/", $submission['calendar'], $matches);
    $meetingData = explode("#", $matches[1][0]);

    $meeting = [
      "email" => $submission['applicant.data.email_address'],
      "name" => $user->getFullName(),
      "phone_number" => null,
      "fiscal_code" => $submission['applicant.data.fiscal_code.data.fiscal_code'],
      "user" => $user,
      "user_message" => isset($submission['user_message']) ? $submission['user_message'] : null,
      "from_time" => $start,
      "to_time" => $end,
      "calendar" => trim($meetingData[0]),
      "meeting_id" => trim($meetingData[1]),
    ];

    if (isset($submission['applicant.data.phone_number'])) {
      $meeting["phone_number"] = $submission['applicant.data.phone_number'];
    } else if (isset($meetingData['applicant.data.cell_number'])) {
      $meeting["phone_number"] = $submission['applicant.data.cell_number'];
    }

    return $meeting;
  }
}
