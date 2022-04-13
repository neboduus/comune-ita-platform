<?php


namespace AppBundle\BackOffice;


use AppBundle\Entity\Calendar;
use AppBundle\Entity\CPSUser;
use AppBundle\Entity\Meeting;
use AppBundle\Entity\Pratica;
use AppBundle\Services\MeetingService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Translation\TranslatorInterface;

class CalendarsBackOffice implements BackOfficeInterface
{
  const IDENTIFIER = 'calendars';

  const NAME = 'Prenotazione appuntamenti';

  const PATH = 'operatori_calendars_index';

  /**
   * @var EntityManagerInterface
   */
  private $em;

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
    Pratica::STATUS_REGISTERED,
    Pratica::STATUS_COMPLETE
  ];

  /**
   * @var LoggerInterface
   */
  private $logger;

  public function __construct(EntityManagerInterface $em, MeetingService $meetingService, TranslatorInterface $translator, LoggerInterface $logger)
  {
    $this->translator = $translator;
    $this->meetingService = $meetingService;
    $this->em = $em;
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

      // Create meeting on activation point
      if (isset($integrations[$status]) && $integrations[$status] == get_class($this)) {
        $this->createMeetingFromPratica($data);
      }

      $linkedMeetings = $data->getMeetings()->toArray();
      // Extract meeting id from calendar string
      preg_match_all("/\(([^\)]*)\)/", $data->getDematerializedForms()['flattened']['calendar'], $matches);
      $meetingId = trim(explode("#", $matches[1][0])[1]);
      $_meeting = $meetingId ? $this->em->getRepository('AppBundle:Meeting')->find($meetingId) : null;
      if ($_meeting) {
        $linkedMeetings[] = $_meeting;
      }

      if (!$linkedMeetings) {
        // Meeting not found, nothing to do
        return [];
      }

      // Application's status change actions
      switch ($status) {
        case Pratica::STATUS_PRE_SUBMIT:
          // link drafts to application
          foreach ($linkedMeetings as $meeting) {
            if ($meeting->getStatus() === Meeting::STATUS_DRAFT) {
              $data->addMeeting($meeting);
            }
          }
          break;
        case Pratica::STATUS_WITHDRAW:
        case Pratica::STATUS_REVOKED:
          // Cancel meeting
          foreach ($linkedMeetings as $meeting) {
            if (in_array($meeting->getStatus(), [Meeting::STATUS_APPROVED, Meeting::STATUS_PENDING]) && ($meeting->getFromTime() > new \DateTime())) {
              $meeting->setStatus(Meeting::STATUS_CANCELLED);
            }
          }
          break;
        case Pratica::STATUS_PAYMENT_PENDING:
          // Increment draft duration
          foreach ($linkedMeetings as $meeting) {
            if ($meeting->getStatus() == Meeting::STATUS_DRAFT) {
              $currentExpiration = clone $meeting->getDraftExpiration() ?? new \DateTime();
              $meeting->setDraftExpiration($currentExpiration->modify('+' . ($meeting->getCalendar()->getDraftsDurationIncrement() ?? Calendar::DEFAULT_DRAFT_INCREMENT) . 'seconds'));
            }
          }
          break;
        case Pratica::STATUS_PAYMENT_ERROR:
        case Pratica::STATUS_CANCELLED:
          // Refuse meeting
          foreach ($linkedMeetings as $meeting) {
            if (in_array($meeting->getStatus(), [Meeting::STATUS_APPROVED, Meeting::STATUS_PENDING]) && ($meeting->getFromTime() > new \DateTime())) {
              $meeting->setStatus(Meeting::STATUS_REFUSED);
            }
          }
          break;
        case Pratica::STATUS_COMPLETE:
          // Approve pending meetings
          foreach ($linkedMeetings as $meeting) {
            if ($meeting->getStatus() == Meeting::STATUS_PENDING) {
              $meeting->setStatus(Meeting::STATUS_APPROVED);
            }
          }
          break;
        default:
          // do nothing
      }
      try {
        foreach ($linkedMeetings as $meeting) {
          $this->meetingService->save($meeting);
        }
        $this->em->flush();
        return $_meeting;
      } catch (\Exception $e) {
        $this->logger->error($this->translator->trans('backoffice.integration.calendars.save_meeting_error') . ' - ' . $e->getMessage());
        return ['error' => $this->translator->trans('backoffice.integration.calendars.save_meeting_error')];
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

      $openingHour = $meetingData['opening_hour'] ? $this->em->getRepository('AppBundle:OpeningHour')->find($meetingData['opening_hour']) : null;

      $meeting->setEmail($meetingData['email']);
      $meeting->setName($meetingData['name']);
      $meeting->setPhoneNumber($meetingData['phone_number']);
      $meeting->setFiscalCode($meetingData['fiscal_code']);
      $meeting->setUser($meetingData['user']);
      $meeting->setUserMessage(isset($meetingData['user_message']) ? $meetingData['user_message'] : null);
      $meeting->setFromTime($meetingData['from_time']);
      $meeting->setToTime($meetingData['to_time']);
      $meeting->setCalendar($calendar);
      if ($openingHour)
        $meeting->setOpeningHour($openingHour);

      if (!empty($this->meetingService->getMeetingErrors($meeting))) {
        // Send email
        $this->meetingService->sendEmailUnavailableMeeting($meeting);
        $this->logger->error($this->translator->trans('backoffice.integration.calendars.invalid_slot'));
        return ['error' => $this->translator->trans('backoffice.integration.calendars.invalid_slot')];
      }

      if ($meeting->getOpeningHour()->getIsModerated() || $meeting->getCalendar()->getIsModerated())
        $meeting->setStatus(Meeting::STATUS_PENDING);
      else
        $meeting->setStatus(Meeting::STATUS_APPROVED);

      $this->meetingService->save($meeting);
      $pratica->addMeeting($meeting);
      $this->em->persist($pratica);
      $this->em->flush();

      return $meeting;
    } catch (\Exception $exception) {
      $this->logger->error($this->translator->trans('backoffice.integration.calendars.save_meeting_error') . ' - ' . $exception->getMessage());
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
      "opening_hour" => isset($meetingData[2]) ? trim($meetingData[2]) : null
    ];

    if (isset($submission['applicant.data.phone_number'])) {
      $meeting["phone_number"] = $submission['applicant.data.phone_number'];
    } else if (isset($meetingData['applicant.data.cell_number'])) {
      $meeting["phone_number"] = $submission['applicant.data.cell_number'];
    }

    return $meeting;
  }
}
