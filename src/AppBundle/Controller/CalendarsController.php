<?php

namespace AppBundle\Controller;

use AppBundle\Entity\AdminUser;
use AppBundle\Entity\Calendar;
use AppBundle\Entity\Meeting;
use AppBundle\Entity\User;
use AppBundle\Services\InstanceService;
use AppBundle\Services\MeetingService;
use DateTime;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use ICal\ICal;
use Omines\DataTablesBundle\Adapter\ArrayAdapter;
use Omines\DataTablesBundle\Adapter\Doctrine\ORMAdapter;
use Omines\DataTablesBundle\Column\DateTimeColumn;
use Omines\DataTablesBundle\Column\NumberColumn;
use Omines\DataTablesBundle\Column\TextColumn;
use Omines\DataTablesBundle\Controller\DataTablesTrait;
use Omines\DataTablesBundle\DataTable;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
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

  public function __construct(TranslatorInterface $translator, EntityManager $em, InstanceService $is, MeetingService $meetingService)
  {
    $this->translator = $translator;
    $this->em = $em;
    $this->is = $is;
    $this->meetingService = $meetingService;
  }

  /**
   * Lists all Calendars
   * @Template()
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

    if (in_array(User::ROLE_OPERATORE, $user->getRoles())) {
      $builder
        ->where('calendar.owner = :owner')
        ->Orwhere('moderators.id = :operatore')
        ->setParameter('owner', $user)
        ->setParameter('operatore', $user);
    }
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
        return sprintf('<a href="%s">%s</a>', $this->generateUrl('operatori_calendar_show', [
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
      ->add('id', TextColumn::class, ['label' => 'Azioni', 'render' => function ($value, $calendar) {
        return sprintf('
        <a class="d-inline-block d-sm-none d-lg-inline-block d-xl-none" href="%s"><svg class="icon icon-sm icon-warning"><use xlink:href="/bootstrap-italia/dist/svg/sprite.svg#it-pencil"></use></svg></a>
        <a class="btn btn-warning btn-sm d-none d-sm-inline-block d-lg-none d-xl-inline-block" href="%s">Modifica</a>
        <a class="d-inline-block d-sm-none d-lg-inline-block d-xl-none" href="%s" onclick="return confirm(\'Sei sicuro di procedere? il calendario verrà eliminato definitivamente.\');"><svg class="icon icon-sm icon-danger"><use xlink:href="/bootstrap-italia/dist/svg/sprite.svg#it-delete"></use></svg></a>
        <a class="btn btn-danger btn-sm d-none d-sm-inline-block d-lg-none d-xl-inline-block" href="%s" onclick="return confirm(\'Sei sicuro di procedere? il calendario verrà eliminato definitivamente.\');">Elimina</a>',
          $this->generateUrl('operatori_calendar_edit', ['calendar' => $value]),
          $this->generateUrl('operatori_calendar_edit', ['calendar' => $value]),
          $this->generateUrl('operatori_calendar_delete', ['id' => $value]),
          $this->generateUrl('operatori_calendar_delete', ['id' => $value])
        );
      }])
      ->createAdapter(ArrayAdapter::class, $data)
      ->handleRequest($request);

    if ($table->isCallback()) {
      return $table->getResponse();
    }
    return array(
      'datatable' => $table
    );
  }

  /**
   * Creates a new Calendar entity.
   * @Template()
   * @Route("/operatori/calendars/new", name="operatori_calendar_new")
   * @Method({"GET", "POST"})
   * @param Request $request the request
   * @return array|RedirectResponse
   * @throws \Exception
   */
  public function newCalendarAction(Request $request)
  {
    $calendar = new Calendar();
    $calendar->setOwner($this->getUser());
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
        $this->addFlash('error', 'Creazione fallita');
      }
    }

    return array(
      'calendar' => $calendar,
      'form' => $form->createView(),
    );
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
    if (!$this->canUserAccessCalendar($calendar))  {
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
   * @Template()
   * @param Request $request the request
   * @param Calendar $calendar The Calendar entity
   *
   * @return array|RedirectResponse
   */
  public function editCalendarAction(Request $request, Calendar $calendar)
  {
    if (!$this->canUserAccessCalendar($calendar))  {
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
        if (!$calendar->getIsModerated()) {
          // remove moderators
          $calendar->setModerators([]);
        }
        $em->persist($calendar);
        $em->flush();

        $this->addFlash('feedback', 'Calendario modificato correttamente');
        return $this->redirectToRoute('operatori_calendars_index');
      } catch (\Exception $exception) {
        $this->addFlash('error', 'Si è verificato un errore durante il salvataggio: ' . $exception->getMessage());
      }
    }

    return [
      'form' => $form->createView(),
      'calendar' => $calendar
    ];
  }

  /**
   * Finds and displays a Calendar entity.
   * @Template()
   * @Route("/operatori/calendars/{calendar}", name="operatori_calendar_show")
   * @throws \Exception
   */
  public function showCalendarAction(Request $request, Calendar $calendar)
  {
    if (!$this->canUserAccessCalendar($calendar))  {
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

    $table = $this->createDataTable()
      ->add('fromTime', DateTimeColumn::class, ['label' => 'Data', 'format' => 'Y-m-d'])
      ->add('id', TextColumn::class, ['label' => 'Orario', 'render' => function ($value, $meeting) {
        return sprintf('%s - %s', $meeting->getFromTime()->format('H:i'), $meeting->getToTime()->format('H:i'));
      }])
      ->add('Nome e Cognome', TextColumn::class, ['label' => 'Utente', 'searchable' => true, 'render' => function ($value, $meeting) {
        if ($meeting->getUser()) return $meeting->getUser()->getUsername();
        else return 'Utente Anonimo';
      }])
      ->add('email', TextColumn::class, ['label' => 'Email', 'searchable' => true, 'render' => function ($value, $meeting) {
        return $value ? sprintf('<a href="mailto:%s"><div class="text-truncate">%s</div></a>', $value, $value) : '---';
      }])
      ->add('phoneNumber', TextColumn::class, ['label' => 'Recapito', 'render' => function ($value, $meeting) {
        return $value ? $value : '---';
      }])
      ->add('rescheduled', NumberColumn::class, ['label' => 'Rinvii'])
      ->add('status', TextColumn::class, ['label' => 'Stato', 'render' => function ($value, $calendar) {
        return $this->getStatusAsString($value);
      }])
      ->addOrderBy('fromTime', DataTable::SORT_DESCENDING)
      ->addOrderBy('id', DataTable::SORT_ASCENDING)
      ->createAdapter(ORMAdapter::class, [
        'entity' => Meeting::class,
        'query' => function (QueryBuilder $builder) use ($calendar) {
          $builder
            ->select('meeting')
            ->from(Meeting::class, 'meeting')
            ->where('meeting.calendar = :calendar')
            ->setParameter('calendar', $calendar);
        },
      ])
      ->handleRequest($request);

    if ($table->isCallback()) {
      return $table->getResponse();
    }

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
      if (!$calendar->getIsModerated()) {
        $color = '#003882';
        $borderColor = '#003882';
        $textColor = 'var(--white)';
      } else {
        switch ($meeting->getStatus()) {
          case 0: // STATUS_PENDING
            $color = 'var(--white)';
            $borderColor = 'var(--primary)';
            $textColor = 'var(--primary)';
            break;
          case 1: // STATUS_APPROVED
            $color = 'var(--primary)';
            $borderColor = 'var(--primary)';
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
          default:
            $color = 'var(--blue)';
            $borderColor = 'var(--blue)';
            $textColor = 'var(--white)';
        }
      }
      $events[] = [
        'id' => $meeting->getId(),
        'title' => $meeting->getName() ? $meeting->getName() : 'Nome non fornito',
        'name' => $meeting->getName(),
        'start' => $meeting->getFromTime()->format('c'),
        'end' => $meeting->getToTime()->format('c'),
        'description' => $meeting->getUserMessage(),
        'email' => $meeting->getEmail(),
        'phoneNumber' => $meeting->getPhoneNumber(),
        'videoconferenceLink' => $meeting->getVideoconferenceLink(),
        'borderColor' => $borderColor,
        'color' => $color,
        'textColor' => $textColor,
        'status' => $meeting->getStatus(),
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
        'defaultSpan'                 => 2,     // Default value
        'defaultTimeZone'             => 'UTC',
        'defaultWeekStart'            => 'MO',  // Default value
        'disableCharacterReplacement' => false, // Default value
        'filterDaysAfter'             => null,  // Default value
        'filterDaysBefore'            => null,  // Default value
        'skipRecurrence'              => false, // Default value
      ));
      $externalCalendars[$externalCalendar->getName()]->initUrl($externalCalendar->getUrl(), $username = null, $password = null, $userAgent = null);
      foreach ($externalCalendars[$externalCalendar->getName()]->events() as $event) {
        $externalEvents[] = [
          'start' => (new DateTime($event->dtstart))->format('c'),
          'end'=>(new DateTime($event->dtend))->format('c'),
          'title'=>$externalCalendar->getName(),
          'uid' =>$event->uid,
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
      $events = array_merge($events, $this->meetingService->getInterval($openingHour));
      $minDuration = min($minDuration, $openingHour->getMeetingMinutes() + $openingHour->getIntervalMinutes());
    }

    // Check permissions if calendar is moderated
    if (!$calendar->getIsModerated() || ($this->getUser() == $calendar->getOwner() || $calendar->getModerators()->contains($this->getUser())))
      $canEdit = true;
    else $canEdit = false;

    $jwt = $this->get('lexik_jwt_authentication.jwt_manager')->create($this->getUser());

    return array(
      'calendar' => $calendar,
      'canEdit' => $canEdit,
      'delete_form' => $deleteForm->createView(),
      'events' => array_values($events),
      'statuses' => $statuses,
      'minDuration' => $minDuration,
      'datatable' => $table,
      'token' => $jwt
    );
  }

  /**
   * Cancels meeting
   * @Route("meetings/{meetingHash}/cancel", name="cancel_meeting")
   * @Template()
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
    return array(
      'form' => $form->createView(),
      'canCancel' => $canCancel,
      'meeting' => $meeting
    );

  }

  /**
   * Approves meeting
   * @Route("operatori/meetings/{id}/approve", name="operatori_approve_meeting")
   * @Template()
   * @param Request $request the request
   * @param Meeting $id The Meeting entity
   *
   * @return array|RedirectResponse|Response
   * @throws \Exception
   */
  public function editMeetingAction(Request $request, $id)
  {
    $em = $this->getDoctrine()->getManager();
    $meeting = $em->getRepository('AppBundle:Meeting')->find($id);
    if (!$meeting)
      return new Response(null, Response::HTTP_NOT_FOUND);

    if ($meeting->getStatus() != Meeting::STATUS_PENDING) {
      return array(
        'form' => null,
        'meeting' => $meeting
      );
    }

    if (!$meeting->getEmail())
      $this->addFlash('warning', $this->translator->trans('meetings.no_email_warning'));


    $form = $this->container->get('form.factory')
      ->createNamedBuilder(null, FormType::class, null, array('csrf_protection' => false))
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
        case 'approve': {$meeting->setStatus(Meeting::STATUS_APPROVED);
        break;}
        case 'refuse': {$meeting->setStatus(Meeting::STATUS_REFUSED);
          break;}
        case 'cancel': {$meeting->setStatus(Meeting::STATUS_CANCELLED);
          break;}
      }
      try {
        $em->persist($meeting);
        $em->flush();

        $this->addFlash('feedback', $this->translator->trans('meetings.email.success'));
      } catch (\Exception $exception) {
        $this->addFlash('error', 'Si è verificato un errore durante la modifica dell\'appuntamento'. $exception->getMessage());
      }
    }
    return array(
      'form' => $form->createView(),
      'meeting' => $meeting
    );
  }

  function getStatusAsString(int $status)
  {
    switch ($status) {
      case 0:
        return 'In attesa di conferma';
        break;
      case 1:
        return 'Approvato';
        break;
      case 2:
        return 'Rifiutato';
        break;
      case 3:
        return 'Assente';
        break;
      case 4:
        return 'Concluso';
        break;
      case 5:
        return 'Annullato';
        break;
      default:
        return 'Errore';
    }
  }
  private function canUserAccessCalendar(Calendar $calendar) {
    $user = $this->getUser();
    if ($user instanceof AdminUser || $calendar->getOwner() == $user || ($calendar->getIsModerated() && $calendar->getModerators()->contains($user))) {
      return true;
    }
    return false;
  }
}

