<?php

namespace App\Controller\Rest;

use App\Entity\Calendar;
use App\Entity\Meeting;
use App\Entity\OpeningHour;
use App\Multitenancy\Annotations\MustHaveTenant;
use App\Multitenancy\TenantAwareFOSRestController;
use App\Services\InstanceService;
use DateInterval;
use DateTime;
use DateTimeZone;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use Nelmio\ApiDocBundle\Annotation\Model;
use Swagger\Annotations as SWG;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class CalendarsAPIController
 * @property EntityManager em
 * @property InstanceService is
 * @package App\Controller
 * @Route("/calendars")
 * @MustHaveTenant()
 */
class CalendarsAPIController extends TenantAwareFOSRestController
{
    const CURRENT_API_VERSION = '1.0';

    public function __construct(EntityManagerInterface $em, InstanceService $is)
    {
        $this->em = $em;
        $this->is = $is;
    }

    /**
     * List all Calendars
     * @Rest\Get("", name="calendars_api_list")
     *
     * @SWG\Response(
     *     response=200,
     *     description="Retrieve list of calendars",
     *     @SWG\Schema(
     *         type="array",
     *         @SWG\Items(ref=@Model(type=Calendar::class))
     *     )
     * )
     * @SWG\Tag(name="calendars")
     */
    public function getCalendars()
    {
        $calendars = $this->getDoctrine()->getRepository('App:Calendar')->findAll();

        return $this->view($calendars, Response::HTTP_OK);
    }

    /**
     * Retreive a Calendar
     * @Rest\Get("/{id}", name="calendar_api_get")
     *
     * @SWG\Response(
     *     response=200,
     *     description="Retreive a Calendar",
     *     @Model(type=Calendar::class)
     * )
     *
     * @SWG\Response(
     *     response=404,
     *     description="Calendar not found"
     * )
     * @SWG\Tag(name="calendars")
     *
     * @param $id
     * @return View
     */
    public function getCalendar($id)
    {
        try {
            $repository = $this->getDoctrine()->getRepository('App:Calendar');
            $result = $repository->find($id);
            if ($result === null) {
                return $this->view("Object not found", Response::HTTP_NOT_FOUND);
            }

            return $this->view($result, Response::HTTP_OK);
        } catch (\Exception $e) {
            return $this->view("Object not found", Response::HTTP_NOT_FOUND);
        }
    }

    /**
     * Retreive a Calendar availabilities
     * @Rest\Get("/{id}/availabilities", name="calendar-availabilities_api_get")
     *
     * @SWG\Response(
     *     response=200,
     *     description="Retreive Calendar's availabilities",
     * )
     *
     * @SWG\Response(
     *     response=404,
     *     description="Calendar not found"
     * )
     * @SWG\Tag(name="calendars")
     *
     * @param $id
     * @param Request $request
     * @return View
     */
    public function getCalendarAvailabilities($id, Request $request)
    {
        $startDate = $request->query->get('from_time');
        $endDate = $request->query->get('to_time');
        try {
            /** @var OpeningHour[] $openingHours */
            $openingHours = $this->getDoctrine()->getRepository('App:OpeningHour')->findBy(['calendar' => $id]);
            /** @var Calendar $calendar */
            $calendar = $this->getDoctrine()->getRepository('App:Calendar')->findOneBy(['id' => $id]);
            if ($calendar === null) {
                return $this->view("Object not found", Response::HTTP_NOT_FOUND);
            }

            // Compute availabilities
            $availabilities = [];

            foreach ($openingHours as $openingHour) {
                if ($startDate && $endDate) {
                    $availabilities = array_merge($availabilities, $openingHour->explodeDays(false, $startDate, $endDate));
                } else {
                    // default: compute availabilities on rolling days
                    $availabilities = array_merge($availabilities, $openingHour->explodeDays());
                }
            }
            // sort and remove duplicates
            sort($availabilities);
            $availabilities = array_unique($availabilities);

            foreach ($calendar->getClosingPeriods() as $closingPeriod) {
                $fromTime = $closingPeriod->getFromTime();
                $toTime = $closingPeriod->getToTime();
                foreach ($availabilities as $availability) {
                    // availability falls inside closing period
                    // todo: check calendar opening hours
                    if ($availability >= $fromTime && $availability <= $toTime) {
                        $key = array_search($availability, $availabilities);
                        unset($availabilities[$key]);
                    }
                }
            }

            return $this->view(array_values($availabilities), Response::HTTP_OK);
        } catch (\Exception $e) {
            return $this->view("Object not found", Response::HTTP_NOT_FOUND);
        }
    }

    /**
     * Retreive a Calendar availabilities by Date
     * @Rest\Get("/{id}/availabilities/{date}", name="calendar-day-availabilities_api_get")
     *
     * @SWG\Response(
     *     response=200,
     *     description="Retreive Calendar's availabilities per date",
     * )
     *
     * @SWG\Response(
     *     response=404,
     *     description="Calendar not found"
     * )
     * @SWG\Tag(name="calendars")
     *
     * @param $id
     * @param $date
     * @param Request $request
     * @return View
     */
    public function getCalendarAvailabilitiesByDate($id, $date, Request $request)
    {
        $allAvailabilities = strtolower($request->get('all') == 'true') ? true : false;

        try {
            /** @var OpeningHour[] $openingHours */
            $openingHours = $this->getDoctrine()->getRepository('App:OpeningHour')->findBy(['calendar' => $id]);
            /** @var Calendar $calendar */
            $calendar = $this->getDoctrine()->getRepository('App:Calendar')->findOneBy(['id' => $id]);
            if ($openingHours === null) {
                return $this->view("Object not found", Response::HTTP_NOT_FOUND);
            }

            try {
                $inputDate = new DateTime($date);
            } catch (\Exception $e) {
                return $this->view('Invalid parameter. ' . $date . ' is not a valid date', Response::HTTP_BAD_REQUEST);
            }

            $slots = array();

            // Retrieve all meetings of a given calendar in a given day grouped by from_time and to_time
            $_meetings = $this->em->createQueryBuilder()
                ->select('count(meeting.fromTime) as count', 'meeting.fromTime as start_time', 'meeting.toTime as end_time')
                ->from('App:Meeting', 'meeting')
                ->where('meeting.calendar = :calendar')
                ->andWhere('meeting.fromTime >= :startDate')
                ->andWhere('meeting.toTime < :endDate')
                ->andWhere('meeting.status != :refused')
                ->andWhere('meeting.status != :cancelled')
                ->setParameter('refused', Meeting::STATUS_REFUSED)
                ->setParameter('cancelled', Meeting::STATUS_CANCELLED)
                ->setParameter('calendar', $calendar)
                ->setParameter('startDate', (new DateTime($date))->setTime(0, 0, 0))
                ->setParameter('endDate', (new DateTime($date))->setTime(23, 59, 59))
                ->groupBy('meeting.fromTime', 'meeting.toTime')
                ->getQuery()->getResult();

            // Set meetings key (Format: start_time-end_time-count)
            $meetings = [];

            foreach ($_meetings as $meeting) {
                if ($meeting['start_time'] instanceof DateTime && $meeting['end_time'] instanceof DateTime) {
                    $meetings[$meeting['start_time']->format('H:i') . '-' . $meeting['end_time']->format('H:i') . '-' . $meeting['count']] = $meeting;
                }
            }

            // Retrieve calendar slots by input date
            foreach ($openingHours as $openingHour) {
                if (in_array($inputDate->format('Y-m-d'), $openingHour->explodeDays($allAvailabilities)) && $openingHour->getStartDate() <= $inputDate && $openingHour->getEndDate() >= $inputDate) {
                    $slots = array_merge($slots, $openingHour->explodeMeetings($inputDate));
                }
            }
            ksort($slots);

            // Set availability of slots
            foreach ($slots as $key => $day) {
                if (array_key_exists($key, $meetings)) {
                    $slots[$key] = $slots[$key] + ['availability' => false];
                } else {
                    if ($allAvailabilities) {
                        $noticeInterval = new DateInterval('PT0H');
                    } else {
                        $noticeInterval = new DateInterval('PT' . $calendar->getMinimumSchedulingNotice() . 'H');
                    }
                    $now = (new DateTime('now', new DateTimeZone('Europe/Rome')))->add($noticeInterval)->format('Y-m-d:H:i');
                    $start = (\DateTime::createFromFormat('Y-m-d:H:i', $day['date'] . ':' . $day['start_time']))->format('Y-m-d:H:i');
                    if ($start <= $now) {
                        $slots[$key] = $slots[$key] + ['availability' => false];
                    } else {
                        $slots[$key] = $slots[$key] + ['availability' => true];
                    }
                }
            }

            return $this->view(array_values($slots), Response::HTTP_OK);
        } catch (\Exception $e) {
            return $this->view("Object not found", Response::HTTP_NOT_FOUND);
        }
    }


    /**
     * Create a Calendar
     * @Rest\Post(name="calendars_api_post")
     *
     * @SWG\Parameter(
     *     name="Authorization",
     *     in="header",
     *     description="The authentication Bearer",
     *     required=true,
     *     type="string"
     * )
     *
     * @SWG\Parameter(
     *     name="Calendar",
     *     in="body",
     *     type="json",
     *     description="The calendar to create",
     *     required=true,
     *     @SWG\Schema(
     *         type="object",
     *         ref=@Model(type=Calendar::class)
     *     )
     * )
     *
     * @SWG\Response(
     *     response=201,
     *     description="Create a Calendar"
     * )
     *
     * @SWG\Response(
     *     response=400,
     *     description="Bad request"
     * )
     * @SWG\Tag(name="calendars")
     *
     * @param Request $request
     * @return View
     * @throws \Exception
     */
    public function postCalendar(Request $request)
    {
        $calendar = new Calendar();

        $form = $this->createForm('App\Form\CalendarType', $calendar);
        $this->processForm($request, $form);
        if (!$form->isValid()) {
            $errors = $this->getErrorsFromForm($form);
            $data = [
                'type' => 'validation_error',
                'title' => 'There was a validation error',
                'errors' => $errors
            ];
            return $this->view($data, Response::HTTP_BAD_REQUEST);
        }

        $em = $this->getDoctrine()->getManager();

        try {
            $em->persist($calendar);
            $em->flush();
        } catch (\Exception $e) {
            $data = [
                'type' => 'error',
                'title' => 'There was an error during save process',
                'description' => $e->getMessage()
            ];
            $this->get('logger')->error(
                $e->getMessage(),
                ['request' => $request]
            );
            return $this->view($data, Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $this->view($calendar, Response::HTTP_CREATED);
    }

    /**
     * @param Request $request
     * @param FormInterface $form
     */
    private function processForm(Request $request, FormInterface $form)
    {
        $data = json_decode($request->getContent(), true);

        $clearMissing = $request->getMethod() != 'PATCH';
        $form->submit($data, $clearMissing);
    }

    /**
     * @param FormInterface $form
     * @return array
     */
    private function getErrorsFromForm(FormInterface $form)
    {
        $errors = array();
        foreach ($form->getErrors() as $error) {
            $errors[] = $error->getMessage();
        }
        foreach ($form->all() as $childForm) {
            if ($childForm instanceof FormInterface) {
                if ($childErrors = $this->getErrorsFromForm($childForm)) {
                    $errors[] = $childErrors;
                }
            }
        }
        return $errors;
    }

    /**
     * Edit full Calendar
     * @Rest\Put("/{id}", name="calendars_api_put")
     *
     * @SWG\Parameter(
     *     name="Authorization",
     *     in="header",
     *     description="The authentication Bearer",
     *     required=true,
     *     type="string"
     * )
     *
     * @SWG\Parameter(
     *     name="Calendar",
     *     in="body",
     *     type="json",
     *     description="The calendar to edit",
     *     required=true,
     *     @SWG\Schema(
     *         type="object",
     *         ref=@Model(type=Calendar::class)
     *     )
     * )
     *
     * @SWG\Response(
     *     response=200,
     *     description="Edit full Calendar"
     * )
     *
     * @SWG\Response(
     *     response=400,
     *     description="Bad request"
     * )
     *
     * @SWG\Response(
     *     response=404,
     *     description="Not found"
     * )
     * @SWG\Tag(name="calendars")
     *
     * @param $id
     * @param Request $request
     * @return View
     */
    public function putCalendar($id, Request $request)
    {
        $repository = $this->getDoctrine()->getRepository('App:Calendar');
        $calendar = $repository->find($id);

        if (!$calendar) {
            return $this->view("Object not found", Response::HTTP_NOT_FOUND);
        }
        $form = $this->createForm('App\Form\CalendarType', $calendar);
        $this->processForm($request, $form);

        if (!$form->isValid()) {
            $errors = $this->getErrorsFromForm($form);
            $data = [
                'type' => 'put_validation_error',
                'title' => 'There was a validation error',
                'errors' => $errors
            ];
            return $this->view($data, Response::HTTP_BAD_REQUEST);
        }

        $em = $this->getDoctrine()->getManager();

        try {
            $em->persist($calendar);
            $em->flush();
        } catch (\Exception $e) {
            $data = [
                'type' => 'error',
                'title' => $e->getMessage()
            ];
            $this->get('logger')->error(
                $e->getMessage(),
                ['request' => $request]
            );
            return $this->view($data, Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $this->view("Object Modified Successfully", Response::HTTP_OK);
    }

    /**
     * Patch a Calendar
     * @Rest\Patch("/{id}", name="calendars_api_patch")
     *
     * @SWG\Parameter(
     *     name="Authorization",
     *     in="header",
     *     description="The authentication Bearer",
     *     required=true,
     *     type="string"
     * )
     *
     * @SWG\Parameter(
     *     name="Calendar",
     *     in="body",
     *     type="json",
     *     description="The calendar to patch",
     *     required=true,
     *     @SWG\Schema(
     *         type="object",
     *         ref=@Model(type=Calendar::class)
     *     )
     * )
     *
     * @SWG\Response(
     *     response=200,
     *     description="Patch a Calendar"
     * )
     *
     * @SWG\Response(
     *     response=400,
     *     description="Bad request"
     * )
     *
     * @SWG\Response(
     *     response=404,
     *     description="Not found"
     * )
     * @SWG\Tag(name="calendars")
     *
     * @param $id
     * @param Request $request
     * @return View
     */
    public function patchCalendar($id, Request $request)
    {
        $repository = $this->getDoctrine()->getRepository('App:Calendar');
        $calendar = $repository->find($id);

        if (!$calendar) {
            return $this->view("Object not found", Response::HTTP_NOT_FOUND);
        }
        $form = $this->createForm('App\Form\CalendarType', $calendar);
        $this->processForm($request, $form);

        if (!$form->isValid()) {
            $errors = $this->getErrorsFromForm($form);
            $data = [
                'type' => 'validation_error',
                'title' => 'There was a validation error',
                'errors' => $errors
            ];
            return $this->view($data, Response::HTTP_BAD_REQUEST);
        }

        try {
            $em = $this->getDoctrine()->getManager();
            $em->persist($calendar);
            $em->flush();
        } catch (\Exception $e) {
            $data = [
                'type' => 'error',
                'title' => 'There was an error during save process'
            ];
            $this->get('logger')->error(
                $e->getMessage(),
                ['request' => $request]
            );
            return $this->view($data, Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $this->view("Object Patched Successfully", Response::HTTP_OK);
    }

    /**
     * Delete a Calendar
     * @Rest\Delete("/{id}", name="calendars_api_delete", methods={"DELETE"})
     *
     * @SWG\Response(
     *     response=204,
     *     description="The resource was deleted successfully."
     * )
     * @SWG\Tag(name="calendars")
     *
     * @param $id
     * @return View
     */
    public function delete($id)
    {
        $calendar = $this->getDoctrine()->getRepository('App:Calendar')->find($id);
        if ($calendar) {
            // debated point: should we 404 on an unknown nickname?
            // or should we just return a nice 204 in all cases?
            // we're doing the latter
            $em = $this->getDoctrine()->getManager();
            try {
                $em->remove($calendar);
                $em->flush();
            } catch (\Exception $e) {
                return $this->view("There was an error during delete process", Response::HTTP_NOT_FOUND);
            }
        }
        return $this->view(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * Retrieve all Opening Hours of a Calendar
     * @Rest\Get("/{calendar_id}/opening-hours", name="opening-hours_api_get")
     *
     * @SWG\Response(
     *     response=200,
     *     description="Retreive the Opening Hours of a Calendar",
     * )
     *
     * @SWG\Parameter(
     *     name="Authorization",
     *     in="header",
     *     description="The authentication Bearer",
     *     required=true,
     *     type="string"
     * )
     *
     * @SWG\Response(
     *     response=404,
     *     description="Opening Hours not found"
     * )
     * @SWG\Tag(name="opening hours")
     * @param $calendar_id
     *
     * @return View
     */
    public function getOpeningHours($calendar_id)
    {
        try {
            $repository = $this->getDoctrine()->getRepository('App:Calendar');
            $calendar = $repository->find($calendar_id);
            if ($calendar === null) {
                return $this->view("Object not found", Response::HTTP_NOT_FOUND);
            }
            return $this->view(['results' => $calendar->getOpeningHours(), 'count' => count($calendar->getOpeningHours())], Response::HTTP_OK);
        } catch (\Exception $e) {
            return $this->view("Object not found", Response::HTTP_NOT_FOUND);
        }
    }

    /**
     * Retrieve an Opening Hour of a Calendar
     * @Rest\Get("/{calendar_id}/opening-hours/{id}", name="opening-hour_api_get")
     *
     * @SWG\Response(
     *     response=200,
     *     description="Retreive an Opening Hour of a Calendar",
     *      @Model(type=OpeningHour::class)
     * )
     *
     * @SWG\Parameter(
     *     name="Authorization",
     *     in="header",
     *     description="The authentication Bearer",
     *     required=true,
     *     type="string"
     * )
     *
     * @SWG\Response(
     *     response=404,
     *     description="Opening Hour not found"
     * )
     * @SWG\Tag(name="opening hours")
     *
     * @param $calendar_id
     * @param $id
     *
     * @return View
     */
    public function getOpeningHour($calendar_id, $id)
    {
        try {
            $repository = $this->getDoctrine()->getRepository('App:OpeningHour');
            $openingHour = $repository->findOneBy(['calendar' => $calendar_id, 'id' => $id]);

            if ($openingHour === null) {
                return $this->view("Object not found", Response::HTTP_NOT_FOUND);
            }
            return $this->view($openingHour, Response::HTTP_OK);
        } catch (\Exception $e) {
            return $this->view("Object not found", Response::HTTP_NOT_FOUND);
        }
    }

    /**
     * Delete an Opening Hour of a Calendar
     * @Rest\Delete("/{calendar_id}/opening-hours/{id}", name="opening-hour_api_delete", methods={"DELETE"})
     *
     * @SWG\Parameter(
     *     name="Authorization",
     *     in="header",
     *     description="The authentication Bearer",
     *     required=true,
     *     type="string"
     * )
     *
     * @SWG\Response(
     *     response=204,
     *     description="The resource was deleted successfully."
     * )
     * @SWG\Tag(name="opening hours")
     *
     * @param $calendar_id
     * @param $id
     *
     * @return View
     */
    public function deleteOpeningHour($calendar_id, $id)
    {
        $repository = $this->getDoctrine()->getRepository('App:OpeningHour');
        $openingHour = $repository->findOneBy(['calendar' => $calendar_id, 'id' => $id]);
        if ($openingHour) {
            // debated point: should we 404 on an unknown nickname?
            // or should we just return a nice 204 in all cases?
            // we're doing the latter
            $em = $this->getDoctrine()->getManager();
            $em->remove($openingHour);
            $em->flush();
        }
        return $this->view(null, Response::HTTP_NO_CONTENT);
    }


    /**
     * Create an Opening Hour
     * @Rest\Post("/{calendar_id}/opening-hours", name="opening-hour_api_post")
     *
     * @SWG\Parameter(
     *     name="Authorization",
     *     in="header",
     *     description="The authentication Bearer",
     *     required=true,
     *     type="string"
     * )
     *
     * @SWG\Parameter(
     *     name="Opening Hour",
     *     in="body",
     *     type="json",
     *     description="The Opening Hour to create",
     *     required=true,
     *     @SWG\Schema(
     *         type="object",
     *         ref=@Model(type=OpeningHour::class)
     *     )
     * )
     *
     * @SWG\Response(
     *     response=201,
     *     description="Create an Opening Hour"
     * )
     *
     * @SWG\Response(
     *     response=400,
     *     description="Bad request"
     * )
     * @SWG\Tag(name="opening hours")
     *
     * @param $calendar_id
     * @param Request $request
     *
     * @return View
     * @throws \Exception
     */

    public function postOpeningHour($calendar_id, Request $request)
    {
        /** @var Calendar $calendar */
        $calendar = $this->em->getRepository('App:Calendar')->find($calendar_id);
        if (!$calendar) {
            return $this->view('Calendar not found', Response::HTTP_BAD_REQUEST);
        }
        $openingHour = new OpeningHour();
        $openingHour->setCalendar($calendar);
        $form = $this->createForm('App\Form\OpeningHourType', $openingHour);
        $this->processForm($request, $form);

        if (!$form->isValid()) {
            $errors = $this->getErrorsFromForm($form);
            $data = [
                'type' => 'validation_error',
                'title' => 'There was a validation error',
                'errors' => $errors
            ];
            return $this->view($data, Response::HTTP_BAD_REQUEST);
        }

        try {
            $this->em->persist($openingHour);
            $this->em->flush();
        } catch (\Exception $e) {
            $data = [
                'type' => 'error',
                'title' => 'There was an error during save process',
                'description' => $e->getMessage()
            ];
            $this->get('logger')->error(
                $e->getMessage(),
                ['request' => $request]
            );
            return $this->view($data, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
        return $this->view($openingHour, Response::HTTP_CREATED);
    }

    /**
     * Edit full Opening Hour
     * @Rest\Put("/{calendar_id}/opening-hours/{id}", name="opening-hour_api_put")
     *
     * @SWG\Parameter(
     *     name="Authorization",
     *     in="header",
     *     description="The authentication Bearer",
     *     required=true,
     *     type="string"
     * )
     *
     * @SWG\Parameter(
     *     name="Opening Hour",
     *     in="body",
     *     type="json",
     *     description="The opening hour to create",
     *     required=true,
     *     @SWG\Schema(
     *         type="object",
     *         ref=@Model(type=OpeningHour::class)
     *     )
     * )
     *
     * @SWG\Response(
     *     response=200,
     *     description="Edit full opening hour"
     * )
     *
     * @SWG\Response(
     *     response=400,
     *     description="Bad request"
     * )
     *
     * @SWG\Response(
     *     response=404,
     *     description="Not found"
     * )
     * @SWG\Tag(name="opening hours")
     *
     * @param Request $request
     * @param $calendar_id
     * @param $id
     *
     * @return View
     */
    public function putOpeningHour($calendar_id, $id, Request $request)
    {
        $repository = $this->getDoctrine()->getRepository('App:OpeningHour');
        $openingHour = $repository->findOneBy(['calendar' => $calendar_id, 'id' => $id]);

        if (!$openingHour) {
            return $this->view("Object not found", Response::HTTP_NOT_FOUND);
        }
        $form = $this->createForm('App\Form\OpeningHourType', $openingHour);
        $this->processForm($request, $form);

        if (!$form->isValid()) {
            $errors = $this->getErrorsFromForm($form);
            $data = [
                'type' => 'put_validation_error',
                'title' => 'There was a validation error',
                'errors' => $errors
            ];
            return $this->view($data, Response::HTTP_BAD_REQUEST);
        }

        try {
            $em = $this->getDoctrine()->getManager();
            $em->persist($openingHour);
            $em->flush();
        } catch (\Exception $e) {
            $data = [
                'type' => 'error',
                'title' => $e->getMessage()
            ];
            $this->get('logger')->error(
                $e->getMessage(),
                ['request' => $request]
            );
            return $this->view($data, Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $this->view("Object Modified Successfully", Response::HTTP_OK);
    }

    /**
     * Patch a Opening Hour
     * @Rest\Patch("/{calendar_id}/opening-hours/{id}", name="opening-hour_api_patch")
     *
     * @SWG\Parameter(
     *     name="Authorization",
     *     in="header",
     *     description="The authentication Bearer",
     *     required=true,
     *     type="string"
     * )
     *
     * @SWG\Parameter(
     *     name="Opening Hour",
     *     in="body",
     *     type="json",
     *     description="The Opening Hour to patch",
     *     required=true,
     *     @SWG\Schema(
     *         type="object",
     *         ref=@Model(type=OpeningHour::class)
     *     )
     * )
     *
     * @SWG\Response(
     *     response=200,
     *     description="Patch a  Opening Hour"
     * )
     *
     * @SWG\Response(
     *     response=400,
     *     description="Bad request"
     * )
     *
     * @SWG\Response(
     *     response=404,
     *     description="Not found"
     * )
     * @SWG\Tag(name="opening hours")
     *
     * @param Request $request
     * @param $calendar_id
     * @param $id
     *
     * @return View
     */
    public function patchOpeningHour($calendar_id, $id, Request $request)
    {
        $repository = $this->getDoctrine()->getRepository('App:OpeningHour');
        $openingHour = $repository->findOneBy(['calendar' => $calendar_id, 'id' => $id]);

        $openingHour->setDaysOfWeek([]);

        if (!$openingHour) {
            return $this->view("Object not found", Response::HTTP_NOT_FOUND);
        }
        $form = $this->createForm('App\Form\OpeningHourType', $openingHour);
        $this->processForm($request, $form);

        if (!$form->isValid()) {
            $errors = $this->getErrorsFromForm($form);
            $data = [
                'type' => 'validation_error',
                'title' => 'There was a validation error',
                'errors' => $errors
            ];
            return $this->view($data, Response::HTTP_BAD_REQUEST);
        }

        try {
            $em = $this->getDoctrine()->getManager();
            $em->persist($openingHour);
            $em->flush();
        } catch (\Exception $e) {
            $data = [
                'type' => 'error',
                'title' => 'There was an error during save process'
            ];
            $this->get('logger')->error(
                $e->getMessage(),
                ['request' => $request]
            );
            return $this->view($data, Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $this->view("Object Patched Successfully", Response::HTTP_OK);
    }
}
