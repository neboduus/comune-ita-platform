<?php

namespace AppBundle\Controller\Ui\Backend;

use AppBundle\Entity\AdminUser;
use AppBundle\Entity\Calendar;
use AppBundle\Entity\CPSUser;
use AppBundle\Entity\Meeting;
use AppBundle\Entity\User;
use AppBundle\Services\InstanceService;
use AppBundle\Services\MeetingService;
use DateTime;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManager;
use ICal\ICal;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Omines\DataTablesBundle\Adapter\ArrayAdapter;
use Omines\DataTablesBundle\Column\TextColumn;
use Omines\DataTablesBundle\Controller\DataTablesTrait;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class CalendarsController
 */
class CalendarsController extends Controller
{
  use DataTablesTrait;

  private $em;

  private $is;

  /**
   * @var TranslatorInterface $translator
   */
  private $translator;

  /**
   * @var MeetingService
   */
  private $meetingService;

  /**
   * @var JWTTokenManagerInterface
   */
  private $JWTTokenManager;

  public function __construct(TranslatorInterface $translator, EntityManager $em, InstanceService $is, MeetingService $meetingService, JWTTokenManagerInterface $JWTTokenManager)
  {
    $this->translator = $translator;
    $this->em = $em;
    $this->is = $is;
    $this->meetingService = $meetingService;
    $this->JWTTokenManager = $JWTTokenManager;
  }

  /**
   * Lists all Calendars
   * @Route("/operatori/calendars", name="operatori_calendars_index")
   */
  public function indexCalendarsAction(Request $request)
  {
    $em = $this->getDoctrine()->getManager();
    $data = [];
    /** @var User $user */
    $user = $this->getUser();

    $builder = $em->createQueryBuilder();
    $builder
      ->select('calendar', 'moderators')
      ->from(Calendar::class, 'calendar')
      ->leftJoin('calendar.moderators', 'moderators');

    $query = $builder->getQuery();

    foreach ($query->getResult() as $calendarEntry) {
      $data[] = array(
        'title' => $calendarEntry->getTitle(),
        'id' => $calendarEntry->getId(),
        'owner' => $calendarEntry->getOwner()->getUsername(),
        'isModerated' => $calendarEntry->getIsModerated(),
      );
    }

    $table = $this->createDataTable()
      ->add('title', TextColumn::class, ['label' => 'Titolo', 'propertyPath' => 'Titolo', 'render' => function ($value, $calendar) {
        /** @var Calendar $cal */
        $cal = $this->em->getRepository('AppBundle:Calendar')->find($calendar['id']);
        $canAccess = $this->canUserAccessCalendar($cal);

        return sprintf('<a class="btn-link %s" href="%s">%s</a>', $canAccess ? "" : "disabled",
          $this->generateUrl('operatori_calendar_show', [
            'calendar' => $calendar['id']
          ]), $calendar['title']);
      }])
      ->add('owner', TextColumn::class, ['label' => 'Proprietario', 'searchable' => true])
      ->add('isModerated', TextColumn::class, ['label' => 'Moderazione', 'render' => function ($value, $calendar) {
        if ($value) {
          return sprintf('<span class="badge badge-outline-primary"><svg class="icon icon-sm icon-primary"><use xlink:href="/bootstrap-italia/dist/svg/sprite.svg#it-locked"></use></svg>Richiede moderazione</span>');
        } else {
          return sprintf('<span class="badge badge-outline-secondary"><svg class="icon icon-sm icon-secondary"><use xlink:href="/bootstrap-italia/dist/svg/sprite.svg#it-unlocked"></use></svg>Non richiede moderazione</span>');
        }
      }])
      ->add('id', TextColumn::class, ['label' => 'Azioni', 'searchable' => false, 'render' => function ($value, $calendar) {
        $cal = $this->em->getRepository('AppBundle:Calendar')->find($value);
        $canAccess = $this->canUserAccessCalendar($cal);
        return sprintf('
        <a class="d-inline-block d-sm-none d-lg-inline-block d-xl-none %s" href="%s"><svg class="icon icon-sm icon-warning"><use xlink:href="/bootstrap-italia/dist/svg/sprite.svg#it-pencil"></use></svg></a>
        <a class="btn btn-warning btn-sm d-none d-sm-inline-block d-lg-none d-xl-inline-block %s" href="%s">Modifica</a>
        <a class="d-inline-block d-sm-none d-lg-inline-block d-xl-none %s" href="%s" onclick="return confirm(\'Sei sicuro di procedere? il calendario verrà eliminato definitivamente.\');"><svg class="icon icon-sm icon-danger"><use xlink:href="/bootstrap-italia/dist/svg/sprite.svg#it-delete"></use></svg></a>
        <a class="btn btn-danger btn-sm d-none d-sm-inline-block d-lg-none d-xl-inline-block %s" href="%s" onclick="return confirm(\'Sei sicuro di procedere? il calendario verrà eliminato definitivamente.\');">Elimina</a>',
          $canAccess ? "" : "disabled",
          $this->generateUrl('operatori_calendar_edit', ['calendar' => $value]),
          $canAccess ? "" : "disabled",
          $this->generateUrl('operatori_calendar_edit', ['calendar' => $value]),
          $canAccess ? "" : "disabled",
          $this->generateUrl('operatori_calendar_delete', ['id' => $value]),
          $canAccess ? "" : "disabled",
          $this->generateUrl('operatori_calendar_delete', ['id' => $value]),
        );
      }])
      ->createAdapter(ArrayAdapter::class, $data)
      ->handleRequest($request);

    if ($table->isCallback()) {
      return $table->getResponse();
    }
    return $this->render('@App/Calendars/indexCalendars.html.twig', [
      'user' => $user,
      'datatable' => $table
    ]);
  }

  /**
   * Creates a new Calendar entity.
   * @Route("/operatori/calendars/new", name="operatori_calendar_new")
   * @Method({"GET", "POST"})
   * @param Request $request the request
   * @return Response
   * @throws \Exception
   */
  public function newCalendarAction(Request $request)
  {
    /** @var User $user */
    $user = $this->getUser();
    $calendar = new Calendar();
    $calendar->setOwner($this->getUser());
    $calendar->setModerators([$this->getUser()]);
    $form = $this->createForm('AppBundle\Form\CalendarBackofficeType', $calendar);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
      $em = $this->getDoctrine()->getManager();

      try {
        $em->persist($calendar);

        foreach ($calendar->getOpeningHours() as $openingHour) {
          $openingHour->setCalendar($calendar);
          $em->persist($openingHour);
        }
        $em->flush();

        $this->addFlash('feedback', 'Calendario creato correttamente');
        return $this->redirectToRoute('operatori_calendars_index');
      } catch (\Exception $exception) {
        if ($exception instanceof UniqueConstraintViolationException) {
          $this->addFlash('error', 'Creazione fallita: esiste già un calendario con nome ' . $calendar->getTitle());
        } else {
          $this->addFlash('error', 'Creazione fallita');
        }
      }
    }

    return $this->render('@App/Calendars/newCalendar.html.twig', [
      'user' => $user,
      'calendar' => $calendar,
      'form' => $form->createView(),
    ]);
  }

  /**
   * Deletes a Calendar entity.
   * @Route("/operatori/calendars/{id}/delete", name="operatori_calendar_delete")
   * @Method("GET")
   * @param Request $request the request
   * @param Calendar $calendar The calendar entity
   * @return RedirectResponse
   */
  public function deleteCalendarAction(Request $request, Calendar $calendar)
  {
    if (!$this->canUserAccessCalendar($calendar)) {
      $this->addFlash('error', 'Non possiedi i permessi per eliminare questo calendario');
      return $this->redirectToRoute('operatori_calendars_index');
    }

    try {
      $em = $this->getDoctrine()->getManager();
      $em->remove($calendar);
      $em->flush();

      $this->addFlash('feedback', 'Calendario eliminato correttamente');

      return $this->redirectToRoute('operatori_calendars_index');
    } catch (ForeignKeyConstraintViolationException $exception) {
      $this->addFlash('warning', 'Impossibile eliminare il calendario');
      return $this->redirectToRoute('operatori_calendars_index');
    }
  }

  /**
   * Creates a form to delete a Calendar entity.
   *
   * @param Calendar $calendar The Calendar entity
   *
   * @return \Symfony\Component\Form\Form The form
   */
  private function createDeleteForm(Calendar $calendar)
  {
    return $this->createFormBuilder()
      ->setAction($this->generateUrl('operatori_calendar_delete', array('id' => $calendar->getId())))
      ->setMethod('DELETE')
      ->getForm();
  }

  /**
   * @Route("operatori/calendars/{calendar}/edit", name="operatori_calendar_edit")
   * @ParamConverter("calendar", class="AppBundle:Calendar")
   * @param Request $request the request
   * @param Calendar $calendar The Calendar entity
   *
   * @return Response
   */
  public function editCalendarAction(Request $request, Calendar $calendar)
  {
    /** @var User $user */
    $user = $this->getUser();

    if (!$this->canUserAccessCalendar($calendar)) {
      $this->addFlash('error', 'Non possiedi i permessi per modificare questo calendario');
      return $this->redirectToRoute('operatori_calendars_index');
    }

    $form = $this->createForm('AppBundle\Form\CalendarBackofficeType', $calendar);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
      $em = $this->getDoctrine()->getManager();

      try {
        $openingHours = $em->getRepository('AppBundle:OpeningHour')->findBy(['calendar' => $calendar]);
        $storedIds = [];
        foreach ($openingHours as $openingHour) {
          $storedIds[$openingHour->getId()] = $openingHour;
        }
        $editIds = [];

        foreach ($calendar->getOpeningHours() as $openingHour) {
          if (!$openingHour->getCalendar()) {
            $openingHour->setCalendar($calendar);
            $em->persist($openingHour);
          } else {
            $editIds[$openingHour->getId()] = $openingHour;
          }
        }
        $toDelete = array_diff_key($storedIds, $editIds);

        foreach ($toDelete as $deleteId => $openingHour) {
          $em->remove($openingHour);
        }
        $em->persist($calendar);
        $em->flush();

        $this->addFlash('feedback', 'Calendario modificato correttamente');
        return $this->redirectToRoute('operatori_calendars_index');
      } catch (\Exception $exception) {
        $this->addFlash('error', 'Si è verificato un errore durante il salvataggio: ' . $exception->getMessage());
      }
    }

    return $this->render('@App/Calendars/editCalendar.html.twig', [
      'user' => $user,
      'form' => $form->createView(),
      'calendar' => $calendar
    ]);
  }

  /**
   * Finds and displays a Calendar entity.
   * @Route("/operatori/calendars/{calendar}", name="operatori_calendar_show")
   * @throws \Exception
   */
  public function showCalendarAction(Request $request, Calendar $calendar)
  {
    /** @var User $user */
    $user = $this->getUser();

    if (!$this->canUserAccessCalendar($calendar)) {
      $this->addFlash('error', 'Non possiedi i permessi per visualizzare questo calendario');
      return $this->redirectToRoute('operatori_calendars_index');
    }

    $statuses = [
      Meeting::STATUS_PENDING => 'In attesa di approvazione',
      Meeting::STATUS_APPROVED => 'Approvato',
      Meeting::STATUS_REFUSED => 'Rifiutato',
      Meeting::STATUS_MISSED => 'Assente',
      Meeting::STATUS_DONE => 'Concluso',
      Meeting::STATUS_CANCELLED => 'Annullato',
    ];

    $em = $this->getDoctrine()->getManager();
    $table = $em->createQueryBuilder()
      ->select('meeting', 'user')
      ->from(Meeting::class, 'meeting')
      ->leftJoin('meeting.user', 'user')
      ->leftJoin('meeting.calendar', 'calendar')
      ->where('meeting.calendar = :calendar')
      ->andWhere('meeting.status != :draft')
      ->setParameter('calendar', $calendar)
      ->setParameter('draft', Meeting::STATUS_DRAFT)
      ->getQuery()->getResult();

    $deleteForm = $this->createDeleteForm($calendar);

    $em = $this->getDoctrine()->getManager();
    $meetings = $em->createQueryBuilder()
      ->select('meeting')
      ->from('AppBundle:Meeting', 'meeting')
      ->where('meeting.calendar = :calendar')
      ->andWhere('meeting.status != :refused')
      ->andWhere('meeting.status != :cancelled')
      ->setParameter('calendar', $calendar)
      ->setParameter('refused', Meeting::STATUS_REFUSED)
      ->setParameter('cancelled', Meeting::STATUS_CANCELLED)
      ->getQuery()->getResult();

    $events = [];
    // meetings
    foreach ($meetings as $meeting) {
      switch ($meeting->getStatus()) {
        case 0: // STATUS_PENDING
          $color = 'var(--white)';
          $borderColor = 'var(--success)';
          $textColor = 'var(--success)';
          break;
        case 1: // STATUS_APPROVED
          $color = 'var(--indigo)';
          $borderColor = 'var(--indigo)';
          $textColor = 'var(--white)';
          break;
        case 2: // STATUS_REFUSED
          $color = 'var(--danger)';
          $borderColor = 'var(--danger)';
          $textColor = 'var(--white)';
          break;
        case 3: // STATUS_MISSED
          $color = 'var(--danger)';
          $borderColor = 'var(--danger)';
          $textColor = 'var(--white)';
          break;
        case 4: // STATUS_DONE
          $color = 'var(--secondary)';
          $borderColor = 'var(--secondary)';
          $textColor = 'var(--white)';
          break;
        case 5: // STATUS_CANCELLED
          $color = 'var(--warning)';
          $borderColor = 'var(--warning)';
          $textColor = 'var(--white)';
          break;
        case 6: // STATUS_DRAFT
          $color = 'var(--light)';
          $borderColor = 'var(--light)';
          $textColor = 'var(--dark)';
          break;
        default:
          $color = 'var(--blue)';
          $borderColor = 'var(--blue)';
          $textColor = 'var(--white)';
      }
      $events[] = [
        'id' => $meeting->getId(),
        'title' => $meeting->getName() ? $meeting->getName() : ($meeting->getStatus() == Meeting::STATUS_DRAFT ? "Bozza" : 'Nome non fornito'),
        'name' => $meeting->getName(),
        'opening_hour' => $meeting->getOpeningHour() ? $meeting->getOpeningHour()->getId() : null,
        'is_allow_overlap' => $calendar->isAllowOverlaps(),
        'start' => $meeting->getFromTime()->format('c'),
        'end' => $meeting->getToTime()->format('c'),
        'description' => $meeting->getUserMessage(),
        'motivation_outcome' => $meeting->getMotivationOutcome(),
        'email' => $meeting->getEmail(),
        'phoneNumber' => $meeting->getPhoneNumber(),
        'videoconferenceLink' => $meeting->getVideoconferenceLink(),
        'borderColor' => $borderColor,
        'color' => $color,
        'textColor' => $textColor,
        'status' => $meeting->getStatus(),
        'draftExpireTime' => $meeting->getDraftExpiration() ? $meeting->getDraftExpiration()->format('c') : null,
        'rescheduled' => $meeting->getRescheduled(),
      ];
    }

    // closures
    foreach ($calendar->getClosingPeriods() as $closingPeriod) {
      $events[] = [
        'title' => 'Chiusura',
        'start' => $closingPeriod->getFromTime()->format('c'),
        'end' => $closingPeriod->getToTime()->format('c'),
        'rendering' => 'background',
        'color' => 'var(--200)'
      ];
    }

    $externalCalendars = [];
    $externalEvents = [];

    foreach ($calendar->getExternalCalendars() as $externalCalendar) {
      $externalCalendars[$externalCalendar->getName()] = new ICal('ICal.ics', array(
        'defaultSpan' => 2,     // Default value
        'defaultTimeZone' => 'UTC',
        'defaultWeekStart' => 'MO',  // Default value
        'disableCharacterReplacement' => false, // Default value
        'filterDaysAfter' => null,  // Default value
        'filterDaysBefore' => null,  // Default value
        'skipRecurrence' => false, // Default value
      ));
      $externalCalendars[$externalCalendar->getName()]->initUrl($externalCalendar->getUrl(), $username = null, $password = null, $userAgent = null);
      foreach ($externalCalendars[$externalCalendar->getName()]->events() as $event) {
        $externalEvents[] = [
          'start' => (new DateTime($event->dtstart))->format('c'),
          'end' => (new DateTime($event->dtend))->format('c'),
          'title' => $externalCalendar->getName(),
          'uid' => $event->uid,
          'borderColor' => 'var(--100)',
          'color' => 'var(--100)',
          'textColor' => 'var(--dark)',
        ];
      }
    }

    $events = array_merge($events, $externalEvents);

    // compute min slot dimension
    $minDuration = PHP_INT_MAX;
    foreach ($calendar->getOpeningHours() as $openingHour) {
      $events = array_merge($events, $this->meetingService->getAbsoluteAvailabilities($openingHour));
      $minDuration = min($minDuration, $openingHour->getMeetingMinutes() + $openingHour->getIntervalMinutes());
    }

    function blockMinutesRound($time, $calculateHour, $minutes = '30', $format = "H:i")
    {
      $seconds = strtotime($time);
      $hour = intval(date("H", $seconds));
      if ($calculateHour && $hour < 19) {
        $rounded = round($seconds / ($minutes * 60)) * ($minutes * 60) + ((20 - $hour) * 3600);
        return date($format, $rounded);
      } else {
        $rounded = round($seconds / ($minutes * 60)) * ($minutes * 60);
        return date($format, $rounded);
      }

    }

    if (count($events) > 0) {
      $minDate = min(array_map(function ($item) {
        return blockMinutesRound($item['start'], false);
      }, $events));
      $maxDate = max(array_map(function ($item) {
        return blockMinutesRound($item['end'], true);
      }, $events));
    } else {
      # Default values for empty events array
      $minDate = Calendar::MIN_DATE;
      $maxDate = Calendar::MAX_DATE;
      $minDuration = Calendar::SLOT_DURATION;
    }

    // Check permissions if calendar is moderated
    if (!$calendar->getIsModerated() || ($this->getUser() == $calendar->getOwner() || $calendar->getModerators()->contains($this->getUser())))
      $canEdit = true;
    else $canEdit = false;

    $jwt = $this->JWTTokenManager->create($this->getUser());

    $data = [
      'user' => $user,
      'calendar' => $calendar,
      'canEdit' => $canEdit,
      'delete_form' => $deleteForm->createView(),
      'events' => array_values($events),
      'statuses' => $statuses,
      'minDuration' => $minDuration,
      'datatable' => $table,
      'token' => $jwt,
      'rangeTimeEvent' => [
        'min' => $minDate,
        'max' => $maxDate
      ]
    ];

    if ($calendar->getType() === Calendar::TYPE_TIME_FIXED) {
      return $this->render('@App/Calendars/showCalendar.html.twig', $data);
    } else {
      return $this->render('@App/Calendars/showDynamicCalendar.html.twig', $data);
    }
  }

  /**
   * Cancels meeting
   * @Route("meetings/{meetingHash}/cancel", name="cancel_meeting")
   * @param Request $request the request
   * @param String $meetingHash The Meeting hash
   *
   * @return array|RedirectResponse|Response
   * @throws \Exception
   */
  public function cancelMeetingAction(Request $request, $meetingHash)
  {
    $em = $this->getDoctrine()->getManager();
    $meeting = $em->getRepository('AppBundle:Meeting')->findOneBy(['cancelLink' => $meetingHash]);
    if (!$meeting)
      return new Response(null, Response::HTTP_NOT_FOUND);

    $limitDate = clone $meeting->getFromTime();
    $limitDate = $limitDate->sub(new \DateInterval('P' . $meeting->getCalendar()->getAllowCancelDays() . 'D'));
    $canCancel = new \DateTime() <= $limitDate;

    $form = $this->createFormBuilder()->add('save', SubmitType::class, [
      'label' => 'Annulla appuntamento',
      'attr' => ['class' => 'btn btn-danger']
    ])->getForm();
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
      try {
        $meeting->setStatus(Meeting::STATUS_CANCELLED);
        $em->persist($meeting);
        $em->flush();

        $this->addFlash('feedback', $this->translator->trans('meetings.email.cancel_success'));
      } catch (\Exception $exception) {
        $this->addFlash('error', 'Si è verificato un errore durante l\'annullamento dell\'appuntamento' . $exception->getMessage());
      }
    }
    return $this->render('@App/Calendars/cancelMeeting.html.twig', [
      'form' => $form->createView(),
      'canCancel' => $canCancel,
      'meeting' => $meeting
    ]);

  }

  /**
   * Approves meeting
   * @Route("operatori/meetings/{id}/approve", name="operatori_approve_meeting")
   * @param Request $request the request
   * @param Meeting $id The Meeting entity
   *
   * @return array|RedirectResponse|Response
   */
  public function editMeetingAction(Request $request, $id)
  {
    $em = $this->getDoctrine()->getManager();
    $meeting = $em->getRepository('AppBundle:Meeting')->find($id);
    if (!$meeting)
      return new Response(null, Response::HTTP_NOT_FOUND);

    if ($meeting->getStatus() != Meeting::STATUS_PENDING) {
      return $this->render('@App/Calendars/editMeeting.html.twig', [
        'form' => null,
        'meeting' => $meeting
      ]);
    }

    if (!$meeting->getEmail())
      $this->addFlash('warning', $this->translator->trans('meetings.no_email_warning'));


    $form = $this->createFormBuilder(null, array('csrf_protection' => false))
      ->add('approve', SubmitType::class, [
        'label' => 'Conferma',
        'attr' => ['class' => 'btn btn-sm btn-success']
      ])
      ->add('refuse', SubmitType::class, [
        'label' => 'Rifiuta',
        'attr' => ['class' => 'btn btn-sm btn-danger']
      ])
      ->add('cancel', SubmitType::class, [
        'label' => 'Annulla',
        'attr' => ['class' => 'btn btn-sm btn-warning']
      ])
      ->getForm();
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
      $status = $form->getClickedButton()->getName();
      switch ($status) {
        case 'approve':
        {
          $meeting->setStatus(Meeting::STATUS_APPROVED);
          break;
        }
        case 'refuse':
        {
          $meeting->setStatus(Meeting::STATUS_REFUSED);
          break;
        }
        case 'cancel':
        {
          $meeting->setStatus(Meeting::STATUS_CANCELLED);
          break;
        }
      }
      try {
        $em->persist($meeting);
        $em->flush();

        $this->addFlash('feedback', $this->translator->trans('meetings.email.success'));
      } catch (\Exception $exception) {
        $this->addFlash('error', 'Si è verificato un errore durante la modifica dell\'appuntamento' . $exception->getMessage());
      }
    }
    return $this->render('@App/Calendars/editMeeting.html.twig', [
      'form' => $form->createView(),
      'meeting' => $meeting
    ]);
  }


  /**
   * Creates a draft meeting.
   * @Route("/meetings/new-draft", name="meetings_create_draft")
   * @Method({"POST"})
   * @param Request $request the request
   * @return Response
   * @throws \Exception
   */
  public function newDraftAction(Request $request)
  {
    /** @var CPSUser $user */
    $user = $this->getUser();
    $date = $request->get('date');
    $slot = $request->get('slot');
    $openingHourId = $request->get('opening_hour');
    $calendarId = $request->get('calendar');
    $meetingId = $request->get('meeting');

    if (!($date && $slot && $calendarId)) {
      return new JsonResponse([
        "error" => "Missing date or slot value"
      ], Response::HTTP_BAD_REQUEST);
    }

    $calendar = $this->em->getRepository('AppBundle:Calendar')->find($calendarId);
    if (!$calendar) {
      return new JsonResponse([
        "error" => "Calendar " . $calendarId . " not found"
      ], Response::HTTP_NOT_FOUND);
    }

    $openingHour = $this->em->getRepository('AppBundle:OpeningHour')->find($openingHourId);
    if (!$openingHour) {
      return new JsonResponse([
        "error" => "Opening hour " . $openingHourId . " not found"
      ], Response::HTTP_NOT_FOUND);
    }

    $meeting = $meetingId ? $this->em->getRepository('AppBundle:Meeting')->find($meetingId) : null;

    $slot = explode('-', $slot);
    $fromTime = new DateTime($date . $slot[0]);
    $toTime = new DateTime($date . $slot[1]);

    if (!$meeting) {
      $meeting = new Meeting();
    } else {
      $meeting = $this->em->getRepository('AppBundle:Meeting')->find($meetingId);
    }

    if ($user)
      $meeting->setUser($user);
    $meeting->setFromTime($fromTime);
    $meeting->setToTime($toTime);
    $meeting->setCalendar($calendar);
    $meeting->setOpeningHour($openingHour);
    $meeting->setUserMessage($this->translator->trans('meetings.default_draft_message'));
    $meeting->setStatus(Meeting::STATUS_DRAFT);
    $meeting->setDraftExpiration(new \DateTime('+' . ($calendar->getDraftsDuration() ?? Calendar::DEFAULT_DRAFT_DURATION) . 'seconds'));

    try {
      $errors = $this->meetingService->getMeetingErrors($meeting);
      if (empty($errors)) {
        $this->em->persist($meeting);
        $this->em->flush();
      } else {
        $this->em->remove($meeting);
        $this->em->flush();

        return new JsonResponse([
          "errors" => $errors
        ], Response::HTTP_BAD_REQUEST);

      }
      return new JsonResponse(["id" => $meeting->getId(), "expiration_time" => $meeting->getDraftExpiration()->format('Y-m-d H:i')], Response::HTTP_OK);
    } catch (\Exception $exception) {
      return new JsonResponse([
        "error" => $exception->getMessage()
      ], Response::HTTP_BAD_REQUEST);
    }
  }


  private function canUserAccessCalendar(Calendar $calendar)
  {
    $user = $this->getUser();
    if ($user instanceof AdminUser || $calendar->getOwner() == $user || $calendar->getModerators()->contains($user)) {
      return true;
    }
    return false;
  }
}

