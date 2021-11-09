<?php


namespace AppBundle\Services;


use AppBundle\Entity\Calendar;
use AppBundle\Entity\Meeting;
use AppBundle\Entity\OpeningHour;
use DateInterval;
use DatePeriod;
use DateTime;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Translation\TranslatorInterface;

class MeetingService
{
  /**
   * @var EntityManager
   */
  protected $entityManager;
  /**
   * @var InstanceService
   */
  private $instanceService;

  /**
   * @var MailerService
   */
  private $mailer;

  private $defaultSender;

  /**
   * @var TranslatorInterface $translator
   */
  private $translator;

  /**
   * @var UrlGeneratorInterface
   */
  private $router;

  public function __construct(
    EntityManagerInterface $entityManager,
    InstanceService        $instanceService,
    MailerService          $mailer, $defaultSender,
    TranslatorInterface    $translator,
    UrlGeneratorInterface  $router
  )
  {
    $this->entityManager = $entityManager;
    $this->instanceService = $instanceService;
    $this->mailer = $mailer;
    $this->defaultSender = $defaultSender;
    $this->translator = $translator;
    $this->router = $router;
  }

  /**
   * Cheks if given slot is available
   * @param Meeting $meeting
   *
   * @return bool
   */
  public function isSlotAvailable(Meeting $meeting)
  {
    $meetings = $this->entityManager->createQueryBuilder()
      ->select('count(meeting) as meetingCount')
      ->from('AppBundle:Meeting', 'meeting')
      ->leftJoin('meeting.calendar', 'calendar')
      ->where('meeting.calendar = :calendar')
      ->andWhere('meeting.fromTime < :toTime AND meeting.toTime > :fromTime')
      ->andWhere('meeting.id != :id')
      ->andWhere('meeting.status != :refused')
      ->andWhere('meeting.status != :cancelled')
      ->setParameter('calendar', $meeting->getCalendar())
      ->setParameter('fromTime', $meeting->getFromTime())
      ->setParameter('toTime', $meeting->getToTime())
      ->setParameter('id', $meeting->getId())
      ->setParameter('refused', Meeting::STATUS_REFUSED)
      ->setParameter('cancelled', Meeting::STATUS_CANCELLED)
      ->getQuery()->getResult();

    if (!empty($meetings) && $meetings[0]['meetingCount'] >= $meeting->getOpeningHour()->getMeetingQueue()) {
      return false;
    }
    return true;
  }

  /**
   * Checks if given slot is valid
   * @param Meeting $meeting
   *
   * @return bool
   * @throws \Exception
   */
  public function isSlotValid(Meeting $meeting)
  {
    // Retrieve all meetings in the same time slot
    foreach ($meeting->getCalendar()->getClosingPeriods() as $closingPeriod) {
      if ($meeting->getFromTime() < $closingPeriod->getToTime() && $closingPeriod->getFromTime() < $meeting->getToTime()) {
        // closure overlap
        return false;
      }
    }

    if ($meeting->getOpeningHour() && $this->isOpeningHourValidForMeeting($meeting, $meeting->getOpeningHour())) {
      // Min duration constraint
      $duration = $this->getDifferenceInMinutes($meeting->getFromTime(), $meeting->getToTime());

      if ($duration < $meeting->getOpeningHour()->getMeetingMinutes()) {
        return false;
      }
      return true;
    } else if (!$meeting->getCalendar()->isAllowOverlaps()) {
      foreach ($meeting->getCalendar()->getOpeningHours() as $openingHour) {
        if ($this->isOpeningHourValidForMeeting($meeting, $openingHour)) {
          $meeting->setOpeningHour($openingHour);
          return true;
        }
      }
    }

    return false;
  }

  private function isOpeningHourValidForMeeting(Meeting $meeting, OpeningHour $openingHour)
  {
    $dates = $this->explodeDays($openingHour, true);
    $meetingDate = $meeting->getFromTime()->format('Y-m-d');

    // Date not available for opening hour
    if (!in_array($meetingDate, $dates))
      return false;

    $isValid = false;
    if ($meeting->getCalendar()->getType() === Calendar::TYPE_TIME_VARIABLE) {
      if ($meeting->getFromTime()->format('H:i') >= $openingHour->getBeginHour()->format('H:i') && $meeting->getToTime()->format('H:i') <= $openingHour->getEndHour()->format('H:i')) {
        $isValid = true;
      }
    } else {
      $slots = $this->explodeMeetings($openingHour, $meeting->getFromTime());
      $meetingEnd = clone $meeting->getToTime();
      $slotKey = $meeting->getFromTime()->format('H:i') . '-' . $meetingEnd->format('H:i');
      if (array_key_exists($slotKey, $slots)) {
        $isValid = true;
      }
    }

    return $isValid;
  }

  /**
   * Returns array of opening hour slots by date
   *
   * @param OpeningHour $openingHour
   * @param DateTime $date
   * @return array
   * @throws \Exception
   */
  public function explodeMeetings(OpeningHour $openingHour, DateTime $date)
  {
    $closures = $openingHour->getCalendar()->getClosingPeriods();
    $intervals = [];
    if ($openingHour->getStartDate() > $date || $openingHour->getEndDate() < $date)
      return $intervals;
    $meetingInterval = new DateInterval('PT' . ($openingHour->getMeetingMinutes() + $openingHour->getIntervalMinutes()) . 'M');
    $dateString = $date->format('Y-m-d');
    $begin = (new DateTime($dateString))->setTime($openingHour->getBeginHour()->format('H'), $openingHour->getBeginHour()->format('i'));
    $end = (new DateTime($dateString))->setTime($openingHour->getEndHour()->format('H'), $openingHour->getEndHour()->format('i'));

    $periods = new DatePeriod($begin, $meetingInterval, $end);
    foreach ($periods as $period) {
      $shoudAdd = true;
      // Check if period falls on closure
      foreach ($closures as $closure) {
        if ($period >= $closure->getFromTime() && $period < $closure->getToTime())
          $shoudAdd = false;
      }

      $_begin = $period;
      $_end = clone $_begin;
      $_end = $_end->add($meetingInterval);
      if ($_end <= $end && $shoudAdd) {
        $intervals[$_begin->format('H:i') . '-' . $_end->modify('- ' . $openingHour->getIntervalMinutes() . ' minutes')->format('H:i')] = [
          'date' => $date->format('Y-m-d'),
          'start_time' => $_begin->format('H:i'),
          'end_time' => $_end->format('H:i'),
          'slots_available' => $openingHour->getMeetingQueue(),
          'opening_hour' => $openingHour->getId()
        ];
      }
    }
    return $intervals;
  }

  /**
   * Return array of available dates
   *
   * @return array
   * @throws \Exception
   */

  function explodeDays(OpeningHour $openingHour, $all = false, $from = NULL, $to = NULL)
  {
    $closures = $openingHour->getCalendar()->getClosingPeriods();
    $array = array();

    if ($all) {
      $start = $openingHour->getStartDate();
      $end = $openingHour->getEndDate();
    } else if ($from) {
      $start = new DateTime($from);
      $end = new DateTime($to);
    } else {
      $noticeInterval = new DateInterval('PT' . $openingHour->getCalendar()->getMinimumSchedulingNotice() . 'H');
      $start = max((new DateTime())->add($noticeInterval), $openingHour->getStartDate());
      $rollingInterval = new DateInterval('P' . $openingHour->getCalendar()->getRollingDays() . 'D');
      $end = min((new DateTime())->add($rollingInterval), $openingHour->getEndDate());
    }
    // Variable that store the date interval of period 1 day
    $interval = new DateInterval('P1D');

    $openingHour->getEndDate()->add($interval);
    $period = new DatePeriod($start, $interval, $end);

    // Use loop to store date into array
    foreach ($period as $date) {
      $shouldAdd = false;
      if (!$closures) $shouldAdd = true;
      foreach ($closures as $closure) {
        $closureStartDay = $closure->getFromTime()->format('Y-m-d');
        $closureEndDay = $closure->getToTime()->format('Y-m-d');
        $day = $date->format('Y-m-d');
        if ($day < $closureStartDay || $day > $closureEndDay) {
          // External
          $shouldAdd = true;
        } else if ($day == $closureStartDay) {
          /* Closure start date equals current date
           Check if opening begin hour is before closure hour */
          $dayOpening = DateTime::createFromFormat('Y-m-d:H:i', $day . ':' . $openingHour->getBeginHour()->format('H:i'));
          if ($dayOpening < $closure->getFromTime()) {
            $shouldAdd = true;
          }
        } else if ($day == $closureEndDay) {
          /* Closure end date equals current date
          Check if opening begin hour is after closure hour*/
          $dayClosure = DateTime::createFromFormat('Y-m-d:H:i', $day . ':' . $openingHour->getEndHour()->format('H:i'));
          if ($closure->getToTime() < $dayClosure) {
            $shouldAdd = true;
          }
        }
      }
      if ($shouldAdd && in_array($date->format('N'), $openingHour->getDaysOfWeek())) {
        $array[] = $date->format('Y-m-d');
      }
    }
    return $array;
  }

  public function getAbsoluteAvailabilities(OpeningHour $openingHour, $all = false, DateTime $from = null, DateTime $to = null)
  {
    $slots = [];
    $startDate = max($from ?? new DateTime(), $openingHour->getStartDate())->format('Y-m-d');
    $endDate = min($to ?? (new DateTime())->modify('+1year'), $openingHour->getEndDate())->format('Y-m-d');
    foreach ($this->explodeDays($openingHour, $all, $startDate, $endDate) as $date) {
      if ($openingHour->getCalendar()->getType() === Calendar::TYPE_TIME_FIXED) {
        foreach ($this->explodeMeetings($openingHour, new DateTime($date)) as $slot) {
          $now = (new DateTime())->format('Y-m-d:H:i');
          $startTime = (\DateTime::createFromFormat('Y-m-d:H:i', $slot['date'] . ':' . $slot['start_time']))->format('Y-m-d:H:i');
          if ($startTime > $now) {
            $start = DateTime::createFromFormat('Y-m-d:H:i', $slot['date'] . ':' . $slot['start_time'])->format('c');
            $end = DateTime::createFromFormat('Y-m-d:H:i', $slot['date'] . ':' . $slot['end_time'])->format('c');
            $slots[] = [
              'title' => 'Apertura',
              'start' => $start,
              'end' => $end,
              'rendering' => 'background',
              'color' => 'var(--blue)'
            ];
          }
        }
      } else {
        $start = DateTime::createFromFormat('Y-m-d:H:i', $date . ':' . $openingHour->getBeginHour()->format('H:i'))->format('c');
        $start = max($start, (new DateTime())->format('c'));
        $end = DateTime::createFromFormat('Y-m-d:H:i', $date . ':' . $openingHour->getEndHour()->format('H:i'))->format('c');
        $slots[] = [
          'title' => 'Apertura',
          'start' => $start,
          'end' => $end,
          'rendering' => 'background',
          'color' => 'var(--blue)'
        ];
      }
    }
    return $slots;
  }

  /**
   * Sends email for new meeting
   *
   * @param Meeting $meeting
   * @throws \Twig\Error\Error
   */
  public function sendEmailNewMeeting(Meeting $meeting)
  {
    $status = $meeting->getStatus();
    $calendar = $meeting->getCalendar();
    $service = count($meeting->getApplications()) > 0 ? $meeting->getApplications()[0]->getServizio()->getName() : "";
    $ente = $this->instanceService->getCurrentInstance();
    $date = $meeting->getFromTime()->format('d/m/Y');
    $hour = $meeting->getFromTime()->format('H:i');
    $contact = $calendar->getContactEmail();

    if ($status == Meeting::STATUS_PENDING) {
      $userMessage = $this->translator->trans('meetings.email.new_meeting.pending', ['%service%' => $service]);
    } else if ($status == Meeting::STATUS_APPROVED) {
      $userMessage = $this->translator->trans('meetings.email.new_meeting.approved',
        [
          '%service%' => $service,
          'hour' => $hour,
          'date' => $date,
          'location' => $calendar->getLocation()
        ]);

      if ($meeting->getMotivationOutcome()) {
        $userMessage = $userMessage . $this->translator->trans('meetings.email.motivation_outcome', [
            '%motivation_outcome%' => $meeting->getMotivationOutcome()
          ]);
      }

      if ($meeting->getVideoconferenceLink()) {
        $userMessage = $userMessage . $this->translator->trans('meetings.email.meeting_link.new', [
            'videoconference_link' => $meeting->getVideoconferenceLink()
          ]);
      }
    } else return;

    // Add link for cancel meeting
    if ($calendar->getContactEmail()) {
      $userMessage = $userMessage . $this->translator->trans('meetings.email.cancel_with_contact', [
          'cancel_link' => $this->router->generate('cancel_meeting', [
            'meetingHash' => $meeting->getCancelLink()
          ], UrlGeneratorInterface::ABSOLUTE_URL),
          'email_address' => $contact
        ]);
    } else {
      $userMessage = $userMessage . $this->translator->trans('meetings.email.cancel_without_contact', [
          'cancel_link' => $this->router->generate('cancel_meeting', [
            'meetingHash' => $meeting->getCancelLink()
          ], UrlGeneratorInterface::ABSOLUTE_URL)
        ]);
    }

    if ($meeting->getEmail()) {
      $this->mailer->dispatchMail(
        $this->defaultSender,
        $ente->getName(),
        $meeting->getEmail(),
        $meeting->getName(),
        $userMessage,
        $this->translator->trans('meetings.email.new_meeting.subject'),
        $ente,
        []
      );
    }

    $operatoreMessage = $this->translator->trans('meetings.email.operatori.new_meeting.message', [
      '%calendar%' => $calendar->getTitle(),
      'date' => $date,
      'hour' => $hour,
      'name' => $meeting->getName(),
      'user_message' => $meeting->getUserMessage()
    ]);

    if ($meeting->getUserMessage()) {
      $operatoreMessage = $operatoreMessage . $this->translator->trans('meetings.email.operatori.new_meeting.reason', [
          '%user_message%' => nl2br($meeting->getUserMessage())
        ]);
    }

    if ($meeting->getStatus() === Meeting::STATUS_PENDING) {
      $operatoreMessage = $operatoreMessage . $this->translator->trans('meetings.email.operatori.new_meeting.approve_link', [
          'approve_link' => $this->router->generate(
            'operatori_approve_meeting',
            ['id' => $meeting->getId()],
            UrlGeneratorInterface::ABSOLUTE_URL)
        ]);
    }

    // Send mail to calendar's contact
    if ($calendar->getContactEmail()) {
      $this->mailer->dispatchMail(
        $this->defaultSender,
        $ente->getName(),
        $calendar->getContactEmail(),
        'Contatto Calendario',
        $operatoreMessage,
        $this->translator->trans('meetings.email.operatori.new_meeting.subject', [
          '%calendar%' => $calendar->getTitle()
        ]),
        $ente,
        []
      );
    }

    // Send email for each moderator
    foreach ($calendar->getModerators() as $moderator) {
      $this->mailer->dispatchMail(
        $this->defaultSender,
        $ente->getName(),
        $moderator->getEmail(),
        $moderator->getNome(),
        $operatoreMessage,
        $this->translator->trans('meetings.email.operatori.new_meeting.subject', [
          '%calendar%' => $calendar->getTitle(),
        ]),
        $ente,
        []
      );
    }
  }

  /**
   * Sends email for updated meeting
   *
   * @param Meeting $meeting
   * @param $changeSet
   * @throws \Twig\Error\Error
   */
  public function sendEmailUpdatedMeeting(Meeting $meeting, $changeSet)
  {
    $statusChanged = key_exists('status', $changeSet);
    $dateChanged = key_exists('fromTime', $changeSet);
    $linkChanged = key_exists('videoconferenceLink', $changeSet);

    if ($dateChanged) {
      $oldDate = $changeSet['fromTime'][0];
    }
    if ($linkChanged) {
      $oldLink = $changeSet['videoconferenceLink'][0];
    }

    $status = $meeting->getStatus();
    $calendar = $meeting->getCalendar();
    $service = count($meeting->getApplications()) > 0 ? $meeting->getApplications()[0]->getServizio()->getName() : "";
    $ente = $this->instanceService->getCurrentInstance();
    $date = $meeting->getFromTime()->format('d/m/Y');
    $hour = $meeting->getFromTime()->format('H:i');
    $location = $calendar->getLocation();
    $contact = $calendar->getContactEmail();
    $link = $meeting->getVideoconferenceLink();

    /*
     * invio email se:
     * l'app.to è stato rifiutato (lo stato è cambiato, non mi interessa la data)
     * Lo stato è approvato (non cambiato) ed è stata cambiata la data
     * Lo stato è cambiato in approvato e ho un cambio di data
     * L'app.to è stato approvato
     */

    $userMessage = '';

    if ($statusChanged && $status == Meeting::STATUS_REFUSED) {
      // Meeting has been refused. Date change does not matter
      $userMessage = $this->translator->trans('meetings.email.edit_meeting.refused', [
        '%service%' => $service,
        'date' => $date,
        'email_address' => $contact
      ]);
    } else if ($statusChanged && $status == Meeting::STATUS_CANCELLED) {
      // Meeting has been cancelled. Date change does not matter
      $userMessage = $this->translator->trans('meetings.email.edit_meeting.cancelled', [
        '%service%' => $service,
        'date' => $date,
        'hour' => $hour
      ]);
    } else if (!$statusChanged && $dateChanged && $status == Meeting::STATUS_APPROVED) {
      // Approved meeting has been rescheduled
      $userMessage = $this->translator->trans('meetings.email.edit_meeting.rescheduled', [
        '%service%' => $service,
        'old_date' => $oldDate->format('d/m/Y'),
        'hour' => $hour,
        'new_date' => $date,
        'location' => $location
      ]);
    } else if ($statusChanged && $dateChanged && $status == Meeting::STATUS_APPROVED) {
      // Auto approved meeting due to date change
      $userMessage = $this->translator->trans('meetings.email.edit_meeting.rescheduled_and_approved', [
        '%service%' => $service,
        'hour' => $hour,
        'date' => $date,
        'location' => $location
      ]);
    } else if ($statusChanged && !$dateChanged && $status == Meeting::STATUS_APPROVED) {
      // Approved meeting with no date change
      $userMessage = $this->translator->trans('meetings.email.edit_meeting.approved', [
        '%service%' => $service,
        'hour' => $hour,
        'date' => $date,
        'location' => $location,
      ]);
    } else if (!$statusChanged && !$dateChanged && $linkChanged && $status == Meeting::STATUS_APPROVED) {
      // Videoconference link changed for approved meeting
      if ($link && $oldLink) {
        $userMessage = $this->translator->trans('meetings.email.meeting_link.changed', [
          '%service%' => $service,
          'videoconference_link' => $link
        ]);
      } else if (!$oldLink) {
        $userMessage = $this->translator->trans('meetings.email.meeting_link.new', [
          '%service%' => $service,
          'videoconference_link' => $link
        ]);
      } else if (!$link) {
        $userMessage = $this->translator->trans('meetings.email.meeting_link.removed', ['%service%' => $service]);
      }

    } else return;

    if ($meeting->getMotivationOutcome()) {
      $userMessage = $userMessage . $this->translator->trans('meetings.email.motivation_outcome', [
          '%motivation_outcome%' => $meeting->getMotivationOutcome()
        ]);
    }

    // Add link for cancel meeting if meeting has status approved
    if ($status == Meeting::STATUS_APPROVED) {
      // Append videoconference link
      if ($link && ($statusChanged || $dateChanged)) {
        $userMessage = $userMessage . $this->translator->trans('meetings.email.meeting_link.message', [
            'videoconference_link' => $link
          ]);
      }
      // Add link for cancel meeting
      if ($calendar->getContactEmail()) {
        $userMessage = $userMessage . $this->translator->trans('meetings.email.cancel_with_contact', [
            'cancel_link' => $this->router->generate('cancel_meeting', [
              'meetingHash' => $meeting->getCancelLink()
            ], UrlGeneratorInterface::ABSOLUTE_URL),
            'email_address' => $contact
          ]);
      } else {
        $userMessage = $userMessage . $this->translator->trans('meetings.email.cancel_without_contact', [
            'cancel_link' => $this->router->generate('cancel_meeting', [
              'meetingHash' => $meeting->getCancelLink()
            ], UrlGeneratorInterface::ABSOLUTE_URL)
          ]);
      }
    }

    if ($meeting->getEmail()) {
      $this->mailer->dispatchMail(
        $this->defaultSender,
        $ente->getName(),
        $meeting->getEmail(),
        $meeting->getName(),
        $userMessage ?? $this->translator->trans('meetings.no_info'),
        $this->translator->trans('meetings.email.edit_meeting.subject'),
        $ente,
        []
      );
    }

    if ($statusChanged && $status == Meeting::STATUS_APPROVED) {
      $contactMessage = $this->translator->trans('meetings.email.operatori.meeting_approved.message', [
        '%calendar%' => $calendar->getTitle(),
        'date' => $date,
        'hour' => $hour
      ]);
      $subject = $this->translator->trans('meetings.email.operatori.meeting_approved.subject', [
        '%calendar%' => $calendar->getTitle()
      ]);
    } else if ($statusChanged && $status == Meeting::STATUS_CANCELLED) {
      $contactMessage = $this->translator->trans('meetings.email.operatori.meeting_cancelled.message', [
        '%calendar%' => $calendar->getTitle(),
        'date' => $date,
        'hour' => $hour
      ]);
      $subject = $this->translator->trans('meetings.email.operatori.meeting_cancelled.subject', [
        '%calendar%' => $calendar->getTitle(),
      ]);
    } else if ($statusChanged && $status == Meeting::STATUS_REFUSED) {
      $contactMessage = $this->translator->trans('meetings.email.operatori.meeting_refused.message', [
        '%calendar%' => $calendar->getTitle(),
        'date' => $date,
        'hour' => $hour
      ]);
      $subject = $this->translator->trans('meetings.email.operatori.meeting_refused.subject', [
        '%calendar%' => $calendar->getTitle(),
      ]);
    } else if ($dateChanged && !$statusChanged) {
      $contactMessage = $this->translator->trans('meetings.email.operatori.meeting_rescheduled.message', [
        '%calendar%' => $calendar->getTitle(),
        '%old_date%' => $oldDate->format('d/m/Y'),
        '%old_hour%' => $oldDate->format('H:i'),
        '%new_date%' => $date,
        '%new_hour%' => $hour
      ]);
      $subject = $this->translator->trans('meetings.email.operatori.meeting_rescheduled.subject', [
        '%calendar%' => $calendar->getTitle(),
      ]);
    }

    $contactMessage = $contactMessage . $this->translator->trans('meetings.email.operatori.meeting_details', [
        '%completename%' => $meeting->getUser()->getFullName(),
        '%start%' => $meeting->getFromTime()->format('d/m/y H:i'),
        '%end%' => $meeting->getToTime()->format('d/m/y H:i'),
        '%reason%' => $meeting->getUserMessage()
      ]);

    if ($meeting->getStatus() === Meeting::STATUS_PENDING) {
      $contactMessage = $contactMessage . $this->translator->trans('meetings.email.operatori.new_meeting.approve_link', [
          'approve_link' => $this->router->generate(
            'operatori_approve_meeting',
            ['id' => $meeting->getId()],
            UrlGeneratorInterface::ABSOLUTE_URL)
        ]);
    }

    if ($calendar->getContactEmail()) {
      $this->mailer->dispatchMail(
        $this->defaultSender,
        $ente->getName(),
        $calendar->getContactEmail(),
        'Contatto Calendario',
        $contactMessage,
        $subject,
        $ente,
        []
      );
    }

    // Send email for each moderator
    foreach ($calendar->getModerators() as $moderator) {
      $this->mailer->dispatchMail(
        $this->defaultSender,
        $ente->getName(),
        $moderator->getEmail(),
        $moderator->getNome(),
        $contactMessage,
        $subject,
        $ente,
        []
      );
    }
  }

  /**
   * Sends email for removed meeting
   *
   * @param Meeting $meeting
   * @throws \Twig\Error\Error
   */
  public function sendEmailRemovedMeeting(Meeting $meeting)
  {
    $service = count($meeting->getApplications()) > 0 ? $meeting->getApplications()[0]->getServizio()->getName() : "";
    $ente = $this->instanceService->getCurrentInstance();

    $message = $this->translator->trans('meetings.email.delete_meeting.delete', [
      '%service%' => $service,
      'date' => $meeting->getFromTime()->format('d/m/Y'),
      'hour' => $meeting->getFromTime()->format('H:i')
    ]);

    if ($meeting->getMotivationOutcome()) {
      $message = $message . $this->translator->trans('meetings.email.motivation_outcome', [
          '%motivation_outcome%' => $meeting->getMotivationOutcome()
        ]);
    }

    if ($meeting->getEmail()) {
      $this->mailer->dispatchMail(
        $this->defaultSender,
        $ente->getName(),
        $meeting->getEmail(),
        $meeting->getName(),
        $message,
        $this->translator->trans('meetings.email.delete_meeting.subject'),
        $ente,
        []
      );
    }
  }


  /**
   * Sends email for unavailable meeting
   *
   * @param Meeting $meeting
   * @throws \Twig\Error\Error
   */
  public function sendEmailUnavailableMeeting(Meeting $meeting)
  {
    $service = count($meeting->getApplications()) > 0 ? $meeting->getApplications()[0]->getServizio()->getName() : "";
    $ente = $this->instanceService->getCurrentInstance();

    $message = $this->translator->trans('meetings.email.invalid_meeting.invalid', [
      '%service%' => $service,
      'date' => $meeting->getFromTime()->format('d/m/Y'),
      'hour' => $meeting->getFromTime()->format('H:i')
    ]);

    if ($meeting->getEmail()) {
      $this->mailer->dispatchMail(
        $this->defaultSender,
        $ente->getName(),
        $meeting->getEmail(),
        $meeting->getName(),
        $message,
        $this->translator->trans('meetings.email.invalid_meeting.subject'),
        $ente,
        []);
    }
  }


  public function getSlottedAvailabilitiesByDate(Calendar $calendar, $date, $all = false, $exludeUnavailable = false, $excludedMeeting = null, $selectedOpeningHours = [])
  {
    /** @var OpeningHour[] $openingHours */
    if ($selectedOpeningHours) {
      foreach ($selectedOpeningHours as $selectedOpeningHour) {
        $openingHour = $this->entityManager->getRepository('AppBundle:OpeningHour')->findOneBy([
          'calendar' => $calendar,
          'id' => $selectedOpeningHour
        ]);
        if ($openingHour) {
          $openingHours[] = $openingHour;
        }
      }
    } else {
      $openingHours = $calendar->getOpeningHours();
    }

    $start = clone ($date)->setTime(0, 0, 0);
    $end = clone ($date)->setTime(23, 59, 59);

    $slots = array();

    $builder = $this->entityManager->createQueryBuilder()
      ->select('count(meeting.fromTime) as count', 'meeting.fromTime as start_time', 'meeting.toTime as end_time')
      ->from('AppBundle:Meeting', 'meeting')
      ->where('meeting.calendar = :calendar')
      ->andWhere('meeting.fromTime >= :startDate')
      ->andWhere('meeting.toTime <= :endDate')
      ->andWhere('meeting.status != :refused')
      ->andWhere('meeting.status != :cancelled')
      ->setParameter('refused', Meeting::STATUS_REFUSED)
      ->setParameter('cancelled', Meeting::STATUS_CANCELLED)
      ->setParameter('calendar', $calendar)
      ->setParameter('startDate', $start)
      ->setParameter('endDate', $end)
      ->groupBy('meeting.fromTime', 'meeting.toTime');

    if ($excludedMeeting) {
      $builder
        ->andWhere('meeting.id != :exluded_id')
        ->setParameter('exluded_id', $excludedMeeting);
    }
    $_meetings = $builder->getQuery()->getResult();

    // Set meetings key (Format: start_time-end_time-count)
    $meetings = [];
    foreach ($_meetings as $meeting) {
      $meetings[$meeting['start_time']->format('H:i') . '-' . $meeting['end_time']->format('H:i')] = $meeting;
    }

    // Retrieve calendar slots by input date
    foreach ($openingHours as $openingHour) {
      if (in_array($date->format('Y-m-d'), $this->explodeDays($openingHour, $all)) && $openingHour->getStartDate() <= $date && $openingHour->getEndDate() >= $date) {
        $slots = array_merge($slots, $this->explodeMeetings($openingHour, $date));
      }
    }
    ksort($slots);

    $availableSlots = [];
    // Set availability of slots
    foreach ($slots as $key => $day) {
      $totalSlotsAvailable = $slots[$key]['slots_available'];
      $slotsUnavailable = 0;
      if (array_key_exists($key, $meetings)) {
        $totalSlotsAvailable = $totalSlotsAvailable - $meetings[$key]['count'];
      } else {
        // Todo: trovare un modo migliore
        foreach ($meetings as $asd => $meeting) {
          // Check availabilities on booked meetings
          $bookedStartTime = $meeting["start_time"];
          $bookedEndTime = $meeting["end_time"];
          $slotStartTime = new DateTime($day["date"] . ' ' . $day["start_time"]);
          $slotEndTime = new DateTime($day["date"] . ' ' . $day["end_time"]);

          if ($bookedEndTime > $slotStartTime && $bookedStartTime < $slotEndTime) {
            $slotsUnavailable = max($slotsUnavailable, $meeting['count'], 0);
          }
        }
      }

      $slots[$key]['availability'] = $slotsUnavailable >= $totalSlotsAvailable ? false : true;
      $slots[$key]['slots_available'] = max($totalSlotsAvailable - $slotsUnavailable, 0);

      if ($all) {
        $noticeInterval = new DateInterval('PT0H');
      } else {
        $noticeInterval = new DateInterval('PT' . $calendar->getMinimumSchedulingNotice() . 'H');
      }
      $now = (new DateTime())->add($noticeInterval)->format('Y-m-d:H:i');
      $start = (\DateTime::createFromFormat('Y-m-d:H:i', $day['date'] . ':' . $day['start_time']))->format('Y-m-d:H:i');

      if ($start <= $now)
        $slots[$key]['availability'] = false;

      if ($slots[$key]['availability'] == true) {
        $availableSlots[$key] = $slots[$key];
      }
    }
    if ($exludeUnavailable) return $availableSlots;
    else return $slots;
  }

  public function getOpeningHoursOverlaps(Calendar $calendar, $selectedOpeningHours = [])
  {

    $overlaps = [];
    /** @var OpeningHour[] $openingHours */
    $openingHours = [];

    if ($selectedOpeningHours) {
      foreach ($selectedOpeningHours as $selectedOpeningHour) {
        $openingHour = $this->entityManager->getRepository('AppBundle:OpeningHour')->findOneBy([
          'calendar' => $calendar,
          'id' => $selectedOpeningHour
        ]);
        if ($openingHour) {
          $openingHours[] = $openingHour;
        }
      }
    } else {
      $openingHours = $calendar->getOpeningHours();
    }

    foreach ($openingHours as $index1 => $openingHour1) {
      foreach ($openingHours as $index2 => $openingHour2) {
        if ($index2 > $index1) {
          // Skip opening hours already analyzed
          $isDatesOverlapped = $openingHour1->getStartDate() < $openingHour2->getEndDate() && $openingHour1->getEndDate() > $openingHour2->getStartDate();
          $isTimesOverlapped = $openingHour1->getBeginHour() < $openingHour2->getEndHour() && $openingHour1->getEndHour() > $openingHour2->getBeginHour();
          $weekDaysOverlapped = array_intersect($openingHour1->getDaysOfWeek(), $openingHour2->getDaysOfWeek());

          if ($isTimesOverlapped && $isDatesOverlapped && !empty($weekDaysOverlapped)) {
            $overlaps[$openingHour1->getId()] = $openingHour1;
            $overlaps[$openingHour2->getId()] = $openingHour2;
          }
        }
      }
    }
    return array_values($overlaps);
  }

  public function getVariableAvailabilitiesByDate(Calendar $calendar, $date, $all = false, $exludeUnavailable = false, $excludedMeeting = null, $selectedOpeningHours = [])
  {
    /** @var OpeningHour[] $openingHours */
    if ($selectedOpeningHours) {
      foreach ($selectedOpeningHours as $selectedOpeningHour) {
        $openingHour = $this->entityManager->getRepository('AppBundle:OpeningHour')->findOneBy([
          'calendar' => $calendar,
          'id' => $selectedOpeningHour
        ]);
        if ($openingHour) {
          $openingHours[] = $openingHour;
        }
      }
    } else {
      $openingHours = $calendar->getOpeningHours();
    }


    $bookedMeetings = $this->getBookedSlotsByDate($date, $calendar, $excludedMeeting);

    if ($all) {
      $noticeInterval = new DateInterval('PT0H');
    } else {
      $noticeInterval = new DateInterval('PT' . $calendar->getMinimumSchedulingNotice() . 'H');
    }
    $firstAvailableDate = (new DateTime())->add($noticeInterval);

    $firstAvailableDate->setTime($firstAvailableDate->format("H"), $firstAvailableDate->format("i"), 0, 0);

    $minute = ($firstAvailableDate->format("i")) % 5;
    if ($minute != 0) {
      $firstAvailableDate->add(new DateInterval("PT" . (5 - $minute) . "M"));
    }


    $timeIntervals = [];
    foreach ($openingHours as $openingHour) {
      if (in_array($date->format('Y-m-d'), $this->explodeDays($openingHour, $all)) && $openingHour->getStartDate() <= $date && $openingHour->getEndDate() >= $date) {
        $begin = (clone $date)->setTime($openingHour->getBeginHour()->format('H'), $openingHour->getBeginHour()->format('i'), 0, 0);
        $end = (clone $date)->setTime($openingHour->getEndHour()->format('H'), $openingHour->getEndHour()->format('i'), 0, 0)->modify('+1minute');
        foreach (new DatePeriod($begin, new DateInterval("PT1M"), $end) as $interval) {
          $timeIntervals[$interval->format('H:i')] = [
            "availabilities" => $interval >= $firstAvailableDate ? $openingHour->getMeetingQueue() : 0,
            "opening_hour" => $openingHour,
            "datetime" => $interval
          ];
        }
      }
    }

    ksort($timeIntervals);

    // Remove bookend meetings
    foreach ($bookedMeetings as $bookedMeeting) {
      foreach (new DatePeriod($bookedMeeting["start_time"], new DateInterval("PT1M"), $bookedMeeting["end_time"]->modify('+' . $bookedMeeting["interval_minutes"] . 'minutes')) as $interval) {
        if (isset($timeIntervals[$interval->format('H:i')]))
          $timeIntervals[$interval->format('H:i')]["availabilities"] = max($timeIntervals[$interval->format('H:i')]["availabilities"] - $bookedMeeting["count"], 0);
      }
    }

    $firstTime = DateTime::createFromFormat('Y-m-d H:i', $date->format('Y-m-d') . ' ' . array_key_first($timeIntervals));
    $endTime = DateTime::createFromFormat('Y-m-d H:i', $date->format('Y-m-d') . ' ' . array_key_last($timeIntervals));

    foreach ($calendar->getClosingPeriods() as $closingPeriod) {
      if ($closingPeriod->getFromTime() <= $endTime && $firstTime <= $closingPeriod->getToTime()) {
        $closure = new DatePeriod(max($closingPeriod->getFromTime(), $firstTime), new DateInterval("PT1M"), min($closingPeriod->getToTime(), $endTime));
        foreach ($closure as $closureInterval) {
          if (isset($timeIntervals[$closureInterval->format("H:i")]))
            $timeIntervals[$closureInterval->format("H:i")]["availabilities"] = 0;
        }
      }
    }

    $slots = array();
    if (empty($timeIntervals))
      return $slots;

    $slotStart = array_key_first($timeIntervals);
    $slotOpeningHour = $timeIntervals[$slotStart]["opening_hour"];
    $slotAvailability = min($timeIntervals[$slotStart]["availabilities"], 1);
    $slotEnd = $slotStart;
    $duration = null;


    foreach ($timeIntervals as $time => $interval) {
      $tmpAvailability = min($interval["availabilities"], 1);
      $tmpOpeningHour = $interval["opening_hour"];
      $slotEnd = $slotOpeningHour === $tmpOpeningHour ? $time : $slotEnd;
      if ($slotAvailability !== $tmpAvailability || $slotOpeningHour !== $tmpOpeningHour) {
        $slots[$slotStart . '-' . $slotEnd] = [
          "date" => $date->format('Y-m-d'),
          "start_time" => $slotStart,
          "end_time" => $slotEnd,
          "slots_available" => $slotAvailability,
          "availability" => $slotAvailability > 0 && $duration >= $slotOpeningHour->getMeetingMinutes(),
          "opening_hour" => $slotOpeningHour->getId(),
          "min_duration" => $slotOpeningHour->getMeetingMinutes(),
        ];

        $slotAvailability = $tmpAvailability;
        $slotOpeningHour = $tmpOpeningHour;
        $slotStart = $time;

      } else {
        $slotEnd = $time;
      }
      $duration = $this->getDifferenceInMinutes($timeIntervals[$slotStart]["datetime"], $timeIntervals[$slotEnd]["datetime"]);
    }

    if ($slotStart !== $slotEnd) {
      $slots[$slotStart . '-' . $slotEnd] = [
        "date" => $date->format('Y-m-d'),
        "start_time" => $slotStart,
        "end_time" => $slotEnd,
        "slots_available" => $slotAvailability,
        "availability" => $slotAvailability > 0 && $duration >= $slotOpeningHour->getMeetingMinutes(),
        "opening_hour" => $slotOpeningHour->getId(),
        "min_duration" => $slotOpeningHour->getMeetingMinutes(),
      ];
    }

    return $slots;
  }

  private function getBookedSlotsByDate($date, $calendar, $excludedMeeting = null)
  {
    $start = clone ($date)->setTime(0, 0, 0);
    $end = clone ($date)->setTime(23, 59, 59);

    $builder = $this->entityManager->createQueryBuilder()
      ->select('count(meeting.fromTime) as count', 'meeting.fromTime as start_time', 'meeting.toTime as end_time', 'opening_hour.intervalMinutes as interval_minutes')
      ->from('AppBundle:Meeting', 'meeting')
      ->join('meeting.openingHour', 'opening_hour')
      ->where('meeting.calendar = :calendar')
      ->andWhere('meeting.fromTime >= :startDate')
      ->andWhere('meeting.toTime < :endDate')
      ->andWhere('meeting.status != :refused')
      ->andWhere('meeting.status != :cancelled')
      ->setParameter('refused', Meeting::STATUS_REFUSED)
      ->setParameter('cancelled', Meeting::STATUS_CANCELLED)
      ->setParameter('calendar', $calendar)
      ->setParameter('startDate', $start)
      ->setParameter('endDate', $end)
      ->groupBy('meeting.fromTime', 'meeting.toTime', 'opening_hour.id');

    if ($excludedMeeting) {
      $builder
        ->andWhere('meeting.id != :exluded_id')
        ->setParameter('exluded_id', $excludedMeeting);
    }
    return $builder->getQuery()->getResult();
  }


  public function getMeetingErrors(Meeting $meeting)
  {
    $errors = [];
    if ($meeting->getCalendar()->isAllowOverlaps() && !$meeting->getOpeningHour()) {
      $errors[] = $this->translator->trans('meetings.error.no_opening_hour_with_overlaps');
    } else {
      if (!$this->isSlotValid($meeting)) {
        $errors[] = $this->translator->trans('meetings.error.slot_invalid');
      }
      if ($meeting->getStatus() !== Meeting::STATUS_REFUSED && $meeting->getStatus() !== Meeting::STATUS_CANCELLED && !$this->isSlotAvailable($meeting)) {
        $errors[] = $this->translator->trans('meetings.error.slot_unavailable');
      }
    }
    return $errors;
  }

  private function getDifferenceInMinutes(DateTime $from, DateTime $to)
  {
    $diff = $from->diff($to);
    return ($diff->h * 60) + ($diff->i);
  }
}
