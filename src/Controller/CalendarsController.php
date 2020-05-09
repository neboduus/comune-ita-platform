<?php

namespace App\Controller;

use App\Entity\Calendar;
use App\Entity\Meeting;
use App\Entity\OpeningHour;
use App\Entity\User;
use App\Multitenancy\Annotations\MustHaveTenant;
use App\Multitenancy\TenantAwareController;
use App\Services\InstanceService;
use App\Services\MeetingService;
use DateTime;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use ICal\ICal;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Omines\DataTablesBundle\Adapter\ArrayAdapter;
use Omines\DataTablesBundle\Adapter\Doctrine\ORMAdapter;
use Omines\DataTablesBundle\Column\DateTimeColumn;
use Omines\DataTablesBundle\Column\NumberColumn;
use Omines\DataTablesBundle\Column\TextColumn;
use Omines\DataTablesBundle\DataTable;
use Omines\DataTablesBundle\DataTableFactory;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

/**
 * @todo correggere le closure render
 * Class CalendarsController
 * @MustHaveTenant()
 */
class CalendarsController extends TenantAwareController
{
    private $em;

    private $is;

    /**
     * @var TranslatorInterface $translator
     */
    private $translator;

    private $dataTableFactory;

    public function __construct(
        TranslatorInterface $translator,
        EntityManagerInterface $em,
        InstanceService $is,
        DataTableFactory $dataTableFactory
    )
    {
        $this->translator = $translator;
        $this->em = $em;
        $this->is = $is;
        $this->dataTableFactory = $dataTableFactory;
    }

    /**
     * Lists all Calendars
     * @Route("/operatori/calendars", name="operatori_calendars_index")
     * @param Request $request
     * @param Environment $twig
     * @return JsonResponse|Response
     */
    public function indexCalendars(Request $request, Environment $twig)
    {
        $data = [];
        /** @var User $user */
        $user = $this->getUser();

        $builder = $this->em->createQueryBuilder();
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

        /** @var Calendar $calendarEntry */
        foreach ($query->getResult() as $calendarEntry) {
            $data[] = array(
                'title' => $calendarEntry->getTitle(),
                'id' => $calendarEntry->getId(),
                'owner' => $calendarEntry->getOwner()->getUsername(),
                'isModerated' => $calendarEntry->getIsModerated(),
            );
        }

        $table = $this->dataTableFactory->create()
            ->add('title', TextColumn::class, ['label' => 'Titolo', 'propertyPath' => 'Titolo', 'render' => function ($value, $calendar) use ($twig) {
                return $twig->render('Calendars/datatable/calendar_title.html.twig', [
                    'value' => $value,
                    'calendar' => $calendar,
                ]);
            }])
            ->add('owner', TextColumn::class, ['label' => 'Proprietario', 'searchable' => true])
            ->add('isModerated', TextColumn::class, ['label' => 'Moderazione', 'render' => function ($value, $calendar) use ($twig) {
                return $twig->render('Calendars/datatable/calendar_is_moderated.html.twig', [
                    'is_moderated' => $value,
                    'calendar' => $calendar,
                ]);
            }])
            ->add('id', TextColumn::class, ['label' => 'Azioni', 'render' => function ($value, $calendar) use ($twig) {
                return $twig->render('Calendars/datatable/calendar_action.html.twig', [
                    'id' => $value,
                    'calendar' => $calendar,
                ]);
            }])
            ->createAdapter(ArrayAdapter::class, $data)
            ->handleRequest($request);

        if ($table->isCallback()) {
            return $table->getResponse();
        }
        return $this->render('Calendars/indexCalendars.html.twig', [
            'datatable' => $table
        ]);
    }

    /**
     * Creates a new Calendar entity.
     * @Route("/operatori/calendars/new", name="operatori_calendar_new", methods={"GET", "POST"})
     * @param Request $request the request
     * @return Response|RedirectResponse
     * @throws \Exception
     */
    public function newCalendar(Request $request)
    {
        $calendar = new Calendar();
        $calendar->setOwner($this->getUser());
        $form = $this->createForm('App\Form\CalendarBackofficeType', $calendar);
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

        return $this->render('Calendars/newCalendar.html.twig', [
            'calendar' => $calendar,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Deletes a Calendar entity.
     * @Route("/operatori/calendars/{id}/delete", name="operatori_calendar_delete", methods={"GET"})
     * @param Calendar $calendar The calendar entity
     * @return RedirectResponse
     */
    public function deleteCalendar(Calendar $calendar)
    {
        if ($calendar->getOwner() != $this->getUser() && !$calendar->getModerators()->contains($this->getUser())) {
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
     * @Route("operatori/calendars/{calendar}/edit", name="operatori_calendar_edit")
     * @ParamConverter("calendar", class="App:Calendar")
     * @param Request $request the request
     * @param Calendar $calendar The Calendar entity
     *
     * @return Response|RedirectResponse
     */
    public function editCalendar(Request $request, Calendar $calendar)
    {
        /** @var User $user */
        $user = $this->getUser();
        if (!in_array(User::ROLE_ADMIN, $user->getRoles()) && $calendar->getOwner()->getId() != $user->getId() && !$calendar->getModerators()->contains($user)) {
            $this->addFlash('error', 'Non possiedi i permessi per modificare questo calendario');
            return $this->redirectToRoute('operatori_calendars_index');
        }

        $form = $this->createForm('App\Form\CalendarBackofficeType', $calendar);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            try {
                /** @var OpeningHour[] $openingHours */
                $openingHours = $em->getRepository('App:OpeningHour')->findBy(['calendar' => $calendar]);
                $storedIds = [];
                foreach ($openingHours as $openingHour) {
                    $storedIds[(string)$openingHour->getId()] = $openingHour;
                }
                $editIds = [];

                foreach ($calendar->getOpeningHours() as $openingHour) {
                    if (!$openingHour->getCalendar()) {
                        $openingHour->setCalendar($calendar);
                        $em->persist($openingHour);
                    } else {
                        $editIds[(string)$openingHour->getId()] = $openingHour;
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

        return $this->render('Calendars/editCalendar.html.twig', [
            'form' => $form->createView(),
            'calendar' => $calendar
        ]);
    }

    /**
     * Finds and displays a Calendar entity.
     * @Route("/operatori/calendars/{calendar}", name="operatori_calendar_show")
     * @param Request $request
     * @param Calendar $calendar
     * @param MeetingService $meetingService
     * @param JWTTokenManagerInterface $JWTTokenManager
     * @return RedirectResponse|Response
     * @throws \Exception
     */
    public function showCalendar(Request $request, Calendar $calendar, MeetingService $meetingService, JWTTokenManagerInterface $JWTTokenManager)
    {
        if ($calendar->getOwner() != $this->getUser() && $calendar->getIsModerated() && !$calendar->getModerators()->contains($this->getUser())) {
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

        $table = $this->dataTableFactory->create()
            ->add('fromTime', DateTimeColumn::class, ['label' => 'Data', 'format' => 'Y-m-d'])
            ->add('id', TextColumn::class, ['label' => 'Orario', 'render' => function ($value, Meeting $meeting) {
                return sprintf('%s - %s', $meeting->getFromTime()->format('H:i'), $meeting->getToTime()->format('H:i'));
            }])
            ->add('Nome e Cognome', TextColumn::class, ['label' => 'Utente', 'searchable' => true, 'render' => function ($value, Meeting $meeting) {
                if ($meeting->getUser()) {
                    return $meeting->getUser()->getUsername();
                } else {
                    return 'Utente Anonimo';
                }
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

        /** @var EntityManagerInterface $em */
        $em = $this->getDoctrine()->getManager();
        /** @var Meeting[] $meetings */
        $meetings = $em->createQueryBuilder()
            ->select('meeting')
            ->from('App:Meeting', 'meeting')
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
            $externalCalendars[$externalCalendar->getName()] = new ICal(false, array(
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
                    'start' => (new DateTime($event->dtstart))->setTimezone(new \DateTimeZone('Europe/Rome'))->format('c'),
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
            $events = array_merge($events, $meetingService->getInterval($openingHour));
            $minDuration = min($minDuration, $openingHour->getMeetingMinutes() + $openingHour->getIntervalMinutes());
        }

        // Check permissions if calendar is moderated
        if (!$calendar->getIsModerated() || ($this->getUser() == $calendar->getOwner() || $calendar->getModerators()->contains($this->getUser()))) {
            $canEdit = true;
        } else {
            $canEdit = false;
        }

        $jwt = $JWTTokenManager->create($this->getUser());

        return $this->render('Calendars/showCalendar.html.twig', [
            'calendar' => $calendar,
            'canEdit' => $canEdit,
            'delete_form' => $deleteForm->createView(),
            'events' => array_values($events),
            'statuses' => $statuses,
            'minDuration' => $minDuration,
            'datatable' => $table,
            'token' => $jwt,
        ]);
    }

    private function getStatusAsString(int $status)
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

    /**
     * Creates a form to delete a Calendar entity.
     * @param Calendar $calendar
     * @return \Symfony\Component\Form\FormInterface
     */
    private function createDeleteForm(Calendar $calendar)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('operatori_calendar_delete', array('id' => $calendar->getId())))
            ->setMethod('DELETE')
            ->getForm();
    }

    /**
     * Cancels meeting
     * @Route("meetings/{meetingHash}/cancel", name="cancel_meeting")
     * @param Request $request the request
     * @param String $meetingHash The Meeting hash
     *
     * @return RedirectResponse|Response
     * @throws \Exception
     */
    public function cancelMeeting(Request $request, $meetingHash)
    {
        $em = $this->getDoctrine()->getManager();
        /** @var Meeting $meeting */
        $meeting = $em->getRepository('App:Meeting')->findOneBy(['cancelLink' => $meetingHash]);
        if (!$meeting) {
            return new Response(null, Response::HTTP_NOT_FOUND);
        }

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

        return $this->render('Calendars/cancelMeeting.html.twig', [
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
     * @throws \Exception
     */
    public function editMeeting(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();
        $meeting = $em->getRepository('App:Meeting')->find($id);
        if (!$meeting) {
            return new Response(null, Response::HTTP_NOT_FOUND);
        }

        if ($meeting->getStatus() != Meeting::STATUS_PENDING) {
            return array(
                'form' => null,
                'meeting' => $meeting
            );
        }

        if (!$meeting->getEmail()) {
            $this->addFlash('warning', $this->translator->trans('meetings.no_email_warning'));
        }

        /** @var Form $form */
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

        return $this->render('Calendars/editMeeting.html.twig', [
            'form' => $form->createView(),
            'meeting' => $meeting
        ]);
    }
}
