<?php


namespace App\Services;


use App\Entity\Calendar;
use App\Entity\Meeting;
use App\Entity\OpeningHour;
use DateInterval;
use DatePeriod;
use DateTime;
use DateTimeZone;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
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
    InstanceService $instanceService,
    MailerService $mailer, $defaultSender,
    TranslatorInterface $translator,
    UrlGeneratorInterface $router)
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
    // Retrieve all meetings in the same time slot
    $meetings = $this->entityManager->createQueryBuilder()
      ->select('openingHour.meetingQueue', 'count(meeting.fromTime) as meetingCount')
      ->from('AppBundle:Meeting', 'meeting')
      ->leftJoin('meeting.calendar', 'calendar')
      ->leftJoin('calendar.openingHours', 'openingHour')
      ->where('meeting.calendar = :calendar')
      ->andWhere('meeting.fromTime = :fromTime')
      ->andWhere('meeting.toTime = :toTime')
      ->andWhere('openingHour.beginHour <= :fromTime')
      ->andWhere('openingHour.endHour >= :toTime')
      ->andWhere('meeting.id != :id')
      ->andWhere('meeting.status != :refused')
      ->andWhere('meeting.status != :cancelled')
      ->setParameter('calendar', $meeting->getCalendar())
      ->setParameter('fromTime', $meeting->getFromTime())
      ->setParameter('toTime', $meeting->getToTime())
      ->setParameter('id', $meeting->getId())
      ->setParameter('refused', Meeting::STATUS_REFUSED)
      ->setParameter('cancelled', Meeting::STATUS_CANCELLED)
      ->groupBy('meeting.fromTime', 'meeting.toTime', 'openingHour.meetingQueue')
      ->getQuery()->getResult();

    if (!empty($meetings) && $meetings[0]['meetingCount'] >= $meetings[0]['meetingQueue']) {
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
      if ($meeting->getToTime() >= $closingPeriod->getFromTime() && $meeting->getFromTime() <= $closingPeriod->getToTime())
        return false;
    }

    $openingHours = $this->entityManager->getRepository('AppBundle:OpeningHour')->findBy(['calendar' => $meeting->getCalendar()]);
    // Check if given date and given slot is correct
    $isValidDate = false;
    $isValidSlot = false;
    foreach ($openingHours as $openingHour) {
      $dates = $this->explodeDays($openingHour, true);
      $meetingDate = $meeting->getFromTime()->format('Y-m-d');
      if (in_array($meetingDate, $dates)) {
        $isValidDate = true;
        $slots = $this->explodeMeetings($openingHour, $meeting->getFromTime());
        $meetingEnd = clone $meeting->getToTime();
        $slotKey = $meeting->getFromTime()->format('H:i') . '-' . $meetingEnd->format('H:i');

        if (array_key_exists($slotKey, $slots)) {
          $isValidSlot = true;
          $meeting->setOpeningHour($openingHour);
        }
      }
    }
    if (!$isValidDate || !$isValidSlot)
      return false;
    return true;
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
          'slots_available' =>  $openingHour->getMeetingQueue()
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

  public function getInterval(OpeningHour $openingHour)
  {
    $slots = [];
    foreach ($this->explodeDays($openingHour, true) as $date) {
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
    $ente = $this->instanceService->getCurrentInstance();
    $date = $meeting->getFromTime()->format('d/m/Y');
    $hour = $meeting->getFromTime()->format('H:i');
    $contact = $calendar->getContactEmail();

    if ($calendar->getIsModerated() && $status == Meeting::STATUS_PENDING) {
      $userMessage = $this->translator->trans('meetings.email.new_meeting.pending');
    } else if ($status == Meeting::STATUS_APPROVED) {
      $userMessage = $this->translator->trans('meetings.email.new_meeting.approved',
        [
          'hour' => $hour,
          'date' => $date,
          'location' => $calendar->getLocation()
        ]);
      if ($meeting->getvideoconferenceLink()) {
        $userMessage = $userMessage . $this->translator->trans('meetings.email.meeting_link', [
            'videoconference_link' => $meeting->getvideoconferenceLink()
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


    // Add mail info
    if ($calendar->getContactEmail()) {
      $userMessage = $userMessage . $this->translator->trans('meetings.email.info_with_contact', [
          'ente' => $ente->getName(),
          'email_address' => $contact
        ]);
    } else {
      $userMessage = $userMessage . $this->translator->trans('meetings.email.info_without_contact', [
          'ente' => $ente->getName()
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
      'date' => $date,
      'hour' => $hour,
      'name' => $meeting->getName(),
      'user_message' => $meeting->getUserMessage()
    ]);

    // Send mail to calendar's contact
    if ($calendar->getContactEmail()) {
      $this->mailer->dispatchMail(
        $this->defaultSender,
        $ente->getName(),
        $calendar->getContactEmail(),
        'Contatto Calendario',
        $operatoreMessage,
        $this->translator->trans('meetings.email.operatori.new_meeting.subject'),
        $ente,
        []
      );
    }

    // Send email for each moderator
    if ($calendar->getIsModerated()) {
      foreach ($calendar->getModerators() as $moderator) {
        $this->mailer->dispatchMail(
          $this->defaultSender,
          $ente->getName(),
          $moderator->getEmail(),
          $moderator->getNome(),
          $operatoreMessage . $this->translator->trans('meetings.email.operatori.new_meeting.approve_link', [
            'approve_link' => $this->router->generate(
              'operatori_approve_meeting',
              [
                'id' => $meeting->getId(),
              ],
              UrlGeneratorInterface::ABSOLUTE_URL)
          ]),
          $this->translator->trans('meetings.email.operatori.new_meeting.subject'),
          $ente,
          []
        );
      }
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
      $oldDate = $changeSet['fromTime'][0]->format('d/m/Y');
    }
    if ($linkChanged) {
      $oldLink = $changeSet['videoconferenceLink'][0];
    }

    $status = $meeting->getStatus();
    $calendar = $meeting->getCalendar();
    $ente = $this->instanceService->getCurrentInstance();
    $date = $meeting->getFromTime()->format('d/m/Y');
    $hour = $meeting->getFromTime()->format('H:i');
    $location = $calendar->getLocation();
    $contact = $calendar->getContactEmail();
    $link = $meeting->getvideoconferenceLink();

    /*
     * invio email se:
     * l'app.to è stato rifiutato (lo stato è cambiato, non mi interessa la data)
     * Lo stato è approvato (non cambiato) ed è stata cambiata la data
     * Lo stato è cambiato in approvato e ho un cambio di data
     * L'app.to è stato approvato
     */


    if ($statusChanged && $status == Meeting::STATUS_REFUSED) {
      // Meeting has been refused. Date change does not matter
      $userMessage = $this->translator->trans('meetings.email.edit_meeting.refused', [
        'date' => $date,
        'email_address' => $contact
      ]);
    } else if ($statusChanged && $status == Meeting::STATUS_CANCELLED) {
      // Meeting has been cancelled. Date change does not matter
      $userMessage = $this->translator->trans('meetings.email.edit_meeting.cancelled', [
        'date' => $date,
        'hour' => $hour
      ]);
    } else if (!$statusChanged && $dateChanged && $status == Meeting::STATUS_APPROVED) {
      // Approved meeting has been rescheduled
      $userMessage = $this->translator->trans('meetings.email.edit_meeting.rescheduled', [
        'old_date' => $oldDate,
        'hour' => $hour,
        'new_date' => $date,
        'location' => $location
      ]);
    } else if ($statusChanged && $dateChanged && $status == Meeting::STATUS_APPROVED) {
      // Auto approved meeting due to date change
      $userMessage = $this->translator->trans('meetings.email.edit_meeting.rescheduled_and_approved', [
        'hour' => $hour,
        'date' => $date,
        'location' => $location
      ]);
    } else if ($statusChanged && !$dateChanged && $status == Meeting::STATUS_APPROVED) {
      // Approved meeting with no date change
      $userMessage = $this->translator->trans('meetings.email.edit_meeting.approved', [
        'hour' => $hour,
        'date' => $date,
        'location' => $location,
      ]);
    } else if (!$statusChanged && !$dateChanged && $linkChanged && $status == Meeting::STATUS_APPROVED) {
      // Videoconference link changed for approved meeting
      if ($link && $oldLink) {
        $userMessage = $this->translator->trans('meetings.email.meeting_link.changed', [
          'videoconference_link' => $link
        ]);
      } else if (!$oldLink) {
        $userMessage = $this->translator->trans('meetings.email.meeting_link.new', [
          'videoconference_link' => $link
        ]);
      } else if (!$link) {
        $userMessage = $this->translator->trans('meetings.email.meeting_link.removed');
      }

    } else return;


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

    // Add mail info
    if ($calendar->getContactEmail()) {
      $userMessage = $userMessage . $this->translator->trans('meetings.email.info_with_contact', [
          'ente' => $ente->getName(),
          'email_address' => $contact
        ]);
    } else {
      $userMessage = $userMessage . $this->translator->trans('meetings.email.info_without_contact', [
          'ente' => $ente->getName()
        ]);
    }

    if ($meeting->getEmail()) {
      $this->mailer->dispatchMail(
        $this->defaultSender,
        $ente->getName(),
        $meeting->getEmail(),
        $meeting->getName(),
        $userMessage,
        $this->translator->trans('meetings.email.edit_meeting.subject'),
        $ente,
        []
      );
    }

    if ($statusChanged && $status == Meeting::STATUS_APPROVED) {
      $contactMessage = $this->translator->trans('meetings.email.operatori.meeting_approved.message', [
        'date' => $date,
        'hour' => $hour
      ]);
      $subject = $this->translator->trans('meetings.email.operatori.meeting_approved.subject');
    } else if ($statusChanged && $status == Meeting::STATUS_CANCELLED) {
      $contactMessage = $this->translator->trans('meetings.email.operatori.meeting_cancelled.message', [
        'date' => $date,
        'hour' => $hour
      ]);
      $subject = $this->translator->trans('meetings.email.operatori.meeting_cancelled.subject');
    } else return;

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
  }

  /**
   * Sends email for removed meeting
   *
   * @param Meeting $meeting
   * @throws \Twig\Error\Error
   */
  public function sendEmailRemovedMeeting(Meeting $meeting)
  {
    $calendar = $meeting->getCalendar();
    $ente = $this->instanceService->getCurrentInstance();

    $message = $this->translator->trans('meetings.email.delete_meeting.delete', [
      'date' => $meeting->getFromTime()->format('d/m/Y'),
      'hour' => $meeting->getFromTime()->format('H:i')
    ]);
    if ($calendar->getContactEmail()) {
      $mailInfo = $this->translator->trans('meetings.email.info_with_contact', [
        'ente' => $ente->getName(),
        'email_address' => $calendar->getContactEmail()
      ]);
    } else {
      $mailInfo = $this->translator->trans('meetings.email.info_without_contact', [
        'ente' => $ente->getName()
      ]);
    }

    $message = $message . $mailInfo;


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
    $calendar = $meeting->getCalendar();
    $ente = $this->instanceService->getCurrentInstance();

    $message = $this->translator->trans('meetings.email.invalid_meeting.invalid', [
      'date' => $meeting->getFromTime()->format('d/m/Y'),
      'hour' => $meeting->getFromTime()->format('H:i')
    ]);
    if ($calendar->getContactEmail()) {
      $mailInfo = $this->translator->trans('meetings.email.info_with_contact', [
        'ente' => $ente->getName(),
        'email_address' => $calendar->getContactEmail()
      ]);
    } else {
      $mailInfo = $this->translator->trans('meetings.email.info_without_contact', [
        'ente' => $ente->getName()
      ]);
    }

    $message = $message . $mailInfo;


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

  public function getAvailabilitiesByDate(Calendar $calendar, $date, $all = false, $exludeUnavailable = false) {
    /** @var OpeningHour[] $openingHours */
    $openingHours = $this->entityManager->getRepository('AppBundle:OpeningHour')->findBy(['calendar' => $calendar]);

    $start = clone($date)->setTime(0, 0, 0);
    $end = clone($date)->setTime(23, 59, 59);

    $slots = array();

    $_meetings = $this->entityManager->createQueryBuilder()
      ->select('count(meeting.fromTime) as count', 'meeting.fromTime as start_time', 'meeting.toTime as end_time')
      ->from('AppBundle:Meeting', 'meeting')
      ->where('meeting.calendar = :calendar')
      ->andWhere('meeting.fromTime >= :startDate')
      ->andWhere('meeting.toTime < :endDate')
      ->andWhere ('meeting.status != :refused')
      ->andWhere ('meeting.status != :cancelled')
      ->setParameter('refused', Meeting::STATUS_REFUSED)
      ->setParameter('cancelled', Meeting::STATUS_CANCELLED)
      ->setParameter('calendar', $calendar)
      ->setParameter('startDate', $start)
      ->setParameter('endDate', $end)
      ->groupBy('meeting.fromTime', 'meeting.toTime')
      ->getQuery()->getResult();

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
      if (array_key_exists($key, $meetings)) {
        $slots[$key]['availability'] = $meetings[$key]['count'] >= $slots[$key]['slots_available'] ? false : true;
        $slots[$key]['slots_available'] = max($slots[$key]['slots_available'] - $meetings[$key]['count'], 0);

      } else {
        if ($all) {
          $noticeInterval = new DateInterval('PT0H');
        } else {
          $noticeInterval = new DateInterval('PT' . $calendar->getMinimumSchedulingNotice() . 'H');
        }
        $now = (new DateTime())->add($noticeInterval)->format('Y-m-d:H:i');
        $start = (\DateTime::createFromFormat('Y-m-d:H:i', $day['date'] . ':' . $day['start_time']))->format('Y-m-d:H:i');
        if ($start <= $now)
          $slots[$key] = $slots[$key] + ['availability' => false];
        else
          $slots[$key] = $slots[$key] + ['availability' => true];
        if ($slots[$key]['availability'] == true) {
          $availableSlots[$key] = $slots[$key];
        }
      }
    }
    if ($exludeUnavailable) return $availableSlots;
    else return $slots;
  }
}
