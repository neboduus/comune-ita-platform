<?php

namespace App\Controller\Rest;

use App\BackOffice\CalendarsBackOffice;
use App\Entity\Calendar;
use App\Entity\OpeningHour;
use App\Security\Voters\BackofficeVoter;
use App\Security\Voters\CalendarVoter;
use App\Services\InstanceService;
use App\Services\MeetingService;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use Symfony\Component\Form\FormInterface;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use OpenApi\Annotations as OA;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use App\Services\Manager\CalendarManager;

/**
 * Class CalendarsAPIController
 * @property EntityManagerInterface em
 * @property InstanceService is
 * @property CalendarManager calendarManager
 * @package App\Controller
 * @Route("/calendars")
 */
class CalendarsAPIController extends AbstractFOSRestController
{
  const CURRENT_API_VERSION = '1.0';

  private $em;

  private $is;

  /** @var MeetingService */
  private $meetingService;

  /** @var LoggerInterface */
  private $logger;

  /** @var CalendarManager  */
  private $calendarManager;

  public function __construct(CalendarManager $calendarManager, EntityManagerInterface $em, InstanceService $is, MeetingService $meetingService, LoggerInterface $logger)
  {
    $this->em = $em;
    $this->is = $is;
    $this->meetingService = $meetingService;
    $this->logger = $logger;
    $this->calendarManager = $calendarManager;
  }


  /**
   * List all Calendars
   * @Rest\Get("", name="calendars_api_list")
   *
   * @OA\Response(
   *     response=200,
   *     description="Retrieve list of calendars",
   *     @OA\JsonContent(
   *         type="array",
   *         @OA\Items(ref=@Model(type=Calendar::class, groups={"read"}))
   *     )
   * )
   *
   * @OA\Parameter(
   *      name="type",
   *      in="query",
   *      @OA\Schema(
   *          type="string"
   *      ),
   *      required=false,
   *      description="Filter results by calendar type"
   *  )
   *
   * @OA\Tag(name="calendars")
   */
  public function getCalendarsAction(Request $request)
  {
    $this->denyAccessUnlessGranted(
      BackofficeVoter::VIEW,
      CalendarsBackOffice::PATH,
      CalendarsBackOffice::IDENTIFIER . ' integration is not enabled on current tenant'
    );

    $type = $request->query->get('type');
    if ($type) {
      $calendars = $this->em->getRepository('App\Entity\Calendar')->findBy(['type' => $type]);
    } else {
      $calendars = $this->em->getRepository('App\Entity\Calendar')->findAll();
    }
    return $this->view($calendars, Response::HTTP_OK);
  }

  /**
   * Retreive a Calendar
   * @Rest\Get("/{id}", name="calendar_api_get")
   *
   * @OA\Response(
   *     response=200,
   *     description="Retreive a Calendar",
   *     @Model(type=Calendar::class, groups={"read"})
   * )
   *
   * @OA\Response(
   *     response=404,
   *     description="Calendar not found"
   * )
   * @OA\Tag(name="calendars")
   *
   * @param $id
   * @return View
   */
  public function getCalendarAction($id)
  {
    $this->denyAccessUnlessGranted(
      BackofficeVoter::VIEW,
      CalendarsBackOffice::PATH,
      CalendarsBackOffice::IDENTIFIER . ' integration is not enabled on current tenant'
    );

    try {
      $repository = $this->em->getRepository('App\Entity\Calendar');
      $result = $repository->find($id);
      if ($result === null) {
        return $this->view(["Object not found"], Response::HTTP_NOT_FOUND);
      }

      return $this->view($result, Response::HTTP_OK);
    } catch (\Exception $e) {
      return $this->view(["Object not found"], Response::HTTP_NOT_FOUND);
    }
  }

  /**
   * Retreive a Calendar availabilities
   * @Rest\Get("/{id}/availabilities", name="calendar-availabilities_api_get")
   *
   * @OA\Response(
   *     response=200,
   *     description="Retreive Calendar's availabilities"
   * )
   *
   * @OA\Parameter(
   *      name="available",
   *      in="query",
   *      @OA\Schema(
   *          type="string"
   *      ),
   *      required=false,
   *      description="Get only available dates: available dates includes at least one available slot"
   *  )
   *
   * @OA\Parameter(
   *      name="from_time",
   *      in="query",
   *      @OA\Schema(
   *          type="string"
   *      ),
   *      required=false,
   *      description="Get availabilities from given date"
   *  )
   *
   * @OA\Parameter(
   *      name="to_time",
   *      in="query",
   *      @OA\Schema(
   *          type="string"
   *      ),
   *      required=false,
   *      description="Get availabilities to given date"
   *  )
   *
   * @OA\Parameter(
   *      name="opening_hours",
   *      in="query",
   *      @OA\Schema(
   *          type="string"
   *      ),
   *      required=false,
   *      description="Get availabilities related to selected opening hours"
   *  )
   *
   * @OA\Response(
   *     response=404,
   *     description="Calendar not found"
   * )
   * @OA\Tag(name="calendars")
   *
   * @param $id
   * @param Request $request
   * @return View
   */
  public function getCalendarAvailabilitiesAction($id, Request $request)
  {
    $this->denyAccessUnlessGranted(
      BackofficeVoter::VIEW,
      CalendarsBackOffice::PATH,
      CalendarsBackOffice::IDENTIFIER . ' integration is not enabled on current tenant'
    );

    $startDate = $request->query->get('from_time');
    $endDate = $request->query->get('to_time');
    $selectedOpeningHours = $request->query->get('opening_hours');
    if ($selectedOpeningHours) {
      $selectedOpeningHours = explode(',', $selectedOpeningHours);
    }
    try {
      $calendar = $this->em->getRepository('App\Entity\Calendar')->findOneBy(['id' => $id]);
      if ($calendar === null) {
        return $this->view(["Object not found"], Response::HTTP_NOT_FOUND);
      }

      /** @var OpeningHour[] $openingHours */
      if ($selectedOpeningHours) {
        $openingHours = $this->em->getRepository('App\Entity\OpeningHour')->findBy(['calendar' => $id, 'id' => $selectedOpeningHours]);
      } else {
        $openingHours = $this->em->getRepository('App\Entity\OpeningHour')->findBy(['calendar' => $id]);
      }

      // Compute availabilities
      $availabilities = [];

      foreach ($openingHours as $openingHour) {
        if ($startDate && $endDate) {
          $availabilities = array_merge($availabilities, $this->meetingService->explodeDays($openingHour, false, $startDate, $endDate));
        } else {
          // default: compute availabilities on rolling days
          $availabilities = array_merge($availabilities, $this->meetingService->explodeDays($openingHour, false));
        }
      }
      // sort and remove duplicates
      sort($availabilities);
      $availabilities = array_unique($availabilities);

      foreach ($calendar->getClosingPeriods() as $closingPeriod) {
        $fromTime = $closingPeriod->getFromTime();
        $toTime = $closingPeriod->getToTime();
        foreach ($availabilities as $availability) {
          if ($availability >= $fromTime && $availability <= $toTime) {
            $key = array_search($availability, $availabilities);
            unset($availabilities[$key]);
          }
        }
      }

      $availableAvailabilities = [];
      foreach ($availabilities as $availability) {
        $availableAvailabilities[] = ['date' => $availability, 'available' => !empty($this->meetingService->getAvailabilitiesByDate($calendar, new DateTime($availability), false, true))];
      }
      return $this->view(array_values($availableAvailabilities), Response::HTTP_OK);
    } catch (\Exception $e) {
      return $this->view(["Object not found"], Response::HTTP_NOT_FOUND);
    }
  }

  /**
   * Retreive a Calendar availabilities by Date
   * @Rest\Get("/{id}/availabilities/{date}", name="calendar-day-availabilities_api_get")
   *
   * @OA\Parameter(
   *      name="all",
   *      in="query",
   *      @OA\Schema(
   *          type="string"
   *      ),
   *      required=false,
   *      description="Get all availabilities apart from calendar configurations"
   *  )
   *
   * @OA\Parameter(
   *      name="exclude",
   *      in="query",
   *      @OA\Schema(
   *          type="string"
   *      ),
   *      required=false,
   *      description="Ignore given meeting availability"
   *  )
   *
   * @OA\Parameter(
   *      name="available",
   *      in="query",
   *      @OA\Schema(
   *          type="string"
   *      ),
   *      required=false,
   *      description="Get only available slots"
   *  )
   *
   * @OA\Parameter(
   *      name="opening_hours",
   *      in="query",
   *      @OA\Schema(
   *          type="string"
   *      ),
   *      required=false,
   *      description="Get availabilities related to selected opening hours"
   *  )
   *
   * @OA\Response(
   *     response=200,
   *     description="Retreive Calendar's availabilities per date",
   * )
   *
   * @OA\Response(
   *     response=404,
   *     description="Calendar not found"
   * )
   * @OA\Tag(name="calendars")
   *
   * @param $id
   * @param $date
   * @param Request $request
   * @return View
   */
  public function getCalendarAvailabilitiesByDateAction($id, $date, Request $request)
  {
    $this->denyAccessUnlessGranted(
      BackofficeVoter::VIEW,
      CalendarsBackOffice::PATH,
      CalendarsBackOffice::IDENTIFIER . ' integration is not enabled on current tenant'
    );

    $allAvailabilities = strtolower($request->get('all') == 'true') ? true : false;
    $excludeUnavailable = $request->get('available');
    $excludedMeeting = $request->query->get('exclude');

    $selectedOpeningHours = $request->query->get('opening_hours');
    if ($selectedOpeningHours) {
      $selectedOpeningHours = explode(',', $selectedOpeningHours);
    }

    $calendar = $this->em->getRepository('App\Entity\Calendar')->findOneBy(['id' => $id]);
    if ($calendar === null) {
      return $this->view(["Object not found"], Response::HTTP_NOT_FOUND);
    }

    try {
      $inputDate = new DateTime($date);
    } catch (\Exception $e) {
      return $this->view(['Invalid parameter. ' . $date . ' is not a valid date'], Response::HTTP_BAD_REQUEST);
    }

    try {
      /** @var OpeningHour[] $openingHours */
      $openingHours = $this->em->getRepository('App\Entity\OpeningHour')->findBy(['calendar' => $id]);
      $calendar = $this->em->getRepository('App\Entity\Calendar')->findOneBy(['id' => $id]);
      if ($openingHours === null) {
        return $this->view(["Object not found"], Response::HTTP_NOT_FOUND);
      }

      $slots = $this->meetingService->getAvailabilitiesByDate($calendar, $inputDate, $allAvailabilities, isset($excludeUnavailable), $excludedMeeting, $selectedOpeningHours);

      if ($selectedOpeningHours) {
        foreach ($slots as $key => $slot) {
          if (!in_array($slot["opening_hour"], $selectedOpeningHours)) {
            unset($slots[$key]);
          }
        }
      }
      return $this->view(array_values($slots), Response::HTTP_OK);
    } catch (\Exception $e) {
      return $this->view(["Object not found"], Response::HTTP_NOT_FOUND);
    }
  }


  /**
   * Create a Calendar
   * @Rest\Post(name="calendars_api_post")
   *
   * @Security(name="Bearer")
   *
   * @OA\RequestBody(
   *     description="The calendar to create",
   *     required=true,
   *     @OA\MediaType(
   *         mediaType="application/json",
   *         @OA\Schema(
   *             type="object",
   *             ref=@Model(type=Calendar::class)
   *         )
   *     )
   * )
   *
   * @OA\Response(
   *     response=201,
   *     description="Create a Calendar"
   * )
   *
   * @OA\Response(
   *     response=400,
   *     description="Bad request"
   * )
   *
   * @OA\Response(
   *     response=403,
   *     description="Access denied"
   * )
   *
   * @OA\Tag(name="calendars")
   *
   * @param Request $request
   * @return View
   * @throws \Exception
   */
  public function postCalendarAction(Request $request)
  {
    $this->denyAccessUnlessGranted(
      BackofficeVoter::VIEW,
      CalendarsBackOffice::PATH,
      CalendarsBackOffice::IDENTIFIER . ' integration is not enabled on current tenant'
    );

    $this->denyAccessUnlessGranted(['ROLE_OPERATORE','ROLE_ADMIN']);

    $calendar = new Calendar();

    $form = $this->createForm('App\Form\CalendarType', $calendar);
    $this->processForm($request, $form);
    if ($form->isSubmitted() && !$form->isValid()) {
      $errors = $this->getErrorsFromForm($form);
      $data = [
        'type' => 'validation_error',
        'title' => 'There was a validation error',
        'errors' => $errors
      ];
      return $this->view($data, Response::HTTP_BAD_REQUEST);
    }

    try {
      $this->calendarManager->save($calendar);
    } catch (\Exception $e) {

      $data = [
        'type' => 'error',
        'title' => 'There was an error during save process',
        'description' => 'Contact technical support at support@opencontent.it'
      ];
      $this->logger->error(
        $e->getMessage(),
        ['request' => $request]
      );
      return $this->view($data, Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    return $this->view($calendar, Response::HTTP_CREATED);
  }

  /**
   * Edit full Calendar
   * @Rest\Put("/{id}", name="calendars_api_put")
   *
   * @Security(name="Bearer")
   *
   * @OA\RequestBody(
   *     description="The calendar to edit",
   *     required=true,
   *     @OA\MediaType(
   *         mediaType="application/json",
   *         @OA\Schema(
   *             type="object",
   *             ref=@Model(type=Calendar::class)
   *         )
   *     )
   * )
   *
   * @OA\Response(
   *     response=200,
   *     description="Edit full Calendar"
   * )
   *
   * @OA\Response(
   *     response=400,
   *     description="Bad request"
   * )
   *
   * @OA\Response(
   *     response=403,
   *     description="Access denied"
   * )
   *
   * @OA\Response(
   *     response=404,
   *     description="Not found"
   * )
   * @OA\Tag(name="calendars")
   *
   * @param $id
   * @param Request $request
   * @return View
   */
  public function putCalendarAction($id, Request $request)
  {
    $this->denyAccessUnlessGranted(
      BackofficeVoter::VIEW,
      CalendarsBackOffice::PATH,
      CalendarsBackOffice::IDENTIFIER . ' integration is not enabled on current tenant'
    );

    $repository = $this->em->getRepository('App\Entity\Calendar');
    $calendar = $repository->find($id);

    if (!$calendar) {
      return $this->view(["Object not found"], Response::HTTP_NOT_FOUND);
    }

    $this->denyAccessUnlessGranted(CalendarVoter::EDIT, $calendar);

    $form = $this->createForm('App\Form\CalendarType', $calendar);
    $this->processForm($request, $form);

    if ($form->isSubmitted() && !$form->isValid()) {
      $errors = $this->getErrorsFromForm($form);
      $data = [
        'type' => 'put_validation_error',
        'title' => 'There was a validation error',
        'errors' => $errors
      ];
      return $this->view($data, Response::HTTP_BAD_REQUEST);
    }

    try {
      $this->calendarManager->save($calendar);
    } catch (\Exception $e) {

      $data = [
        'type' => 'error',
        'title' => 'There was an error during save process',
        'description' => 'Contact technical support at support@opencontent.it'
      ];
      $this->logger->error(
        $e->getMessage(),
        ['request' => $request]
      );
      return $this->view($data, Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    return $this->view(["Object Modified Successfully"], Response::HTTP_OK);
  }

  /**
   * Patch a Calendar
   * @Rest\Patch("/{id}", name="calendars_api_patch")
   *
   * @Security(name="Bearer")
   *
   * @OA\RequestBody(
   *     description="The calendar to patch",
   *     required=true,
   *     @OA\MediaType(
   *         mediaType="application/json",
   *         @OA\Schema(
   *             type="object",
   *             ref=@Model(type=Calendar::class)
   *         )
   *     )
   * )
   *
   * @OA\Response(
   *     response=200,
   *     description="Patch a Calendar"
   * )
   *
   * @OA\Response(
   *     response=400,
   *     description="Bad request"
   * )
   *
   * @OA\Response(
   *     response=403,
   *     description="Access denied"
   * )
   *
   * @OA\Response(
   *     response=404,
   *     description="Not found"
   * )
   * @OA\Tag(name="calendars")
   *
   * @param $id
   * @param Request $request
   * @return View
   */
  public function patchCalendarAction($id, Request $request)
  {
    $this->denyAccessUnlessGranted(
      BackofficeVoter::VIEW,
      CalendarsBackOffice::PATH,
      CalendarsBackOffice::IDENTIFIER . ' integration is not enabled on current tenant'
    );

    $repository = $this->em->getRepository('App\Entity\Calendar');
    $calendar = $repository->find($id);

    if (!$calendar) {
      return $this->view(["Object not found"], Response::HTTP_NOT_FOUND);
    }

    $this->denyAccessUnlessGranted(CalendarVoter::EDIT, $calendar);

    $form = $this->createForm('App\Form\CalendarType', $calendar);
    $this->processForm($request, $form);

    if ($form->isSubmitted() && !$form->isValid()) {
      $errors = $this->getErrorsFromForm($form);
      $data = [
        'type' => 'validation_error',
        'title' => 'There was a validation error',
        'errors' => $errors
      ];
      return $this->view($data, Response::HTTP_BAD_REQUEST);
    }

    try {
      $this->calendarManager->save($calendar);
    } catch (\Exception $e) {

      $data = [
        'type' => 'error',
        'title' => 'There was an error during save process',
        'description' => 'Contact technical support at support@opencontent.it'
      ];
      $this->logger->error(
        $e->getMessage(),
        ['request' => $request]
      );
      return $this->view($data, Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    return $this->view(["Object Patched Successfully"], Response::HTTP_OK);
  }

  /**
   * Delete a Calendar
   * @Rest\Delete("/{id}", name="calendars_api_delete")
   *
   * @Security(name="Bearer")
   *
   * @OA\Response(
   *     response=204,
   *     description="The resource was deleted successfully."
   * )
   *
   * @OA\Response(
   *     response=403,
   *     description="Access denied"
   * )
   *
   * @OA\Tag(name="calendars")
   *
   * @Method("DELETE")
   * @param $id
   * @return View
   */
  public function deleteAction($id)
  {
    $this->denyAccessUnlessGranted(
      BackofficeVoter::VIEW,
      CalendarsBackOffice::PATH,
      CalendarsBackOffice::IDENTIFIER . ' integration is not enabled on current tenant'
    );

    $calendar = $this->em->getRepository('App\Entity\Calendar')->find($id);
    if ($calendar) {
      $this->denyAccessUnlessGranted(CalendarVoter::DELETE, $calendar);

      try {
        $this->em->remove($calendar);
        $this->em->flush();
      } catch (\Exception $e) {
        return $this->view(["There was an error during delete process"], Response::HTTP_NOT_FOUND);
      }

    }
    return $this->view(null, Response::HTTP_NO_CONTENT);
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
   * Retrieve all Opening Hours of a Calendar
   * @Rest\Get("/{calendar_id}/opening-hours", name="opening-hours_api_get")
   *
   * @OA\Response(
   *     response=200,
   *     description="Retreive the Opening Hours of a Calendar",
   *     @OA\JsonContent(
   *         type="array",
   *         @OA\Items(ref=@Model(type=OpeningHour::class))
   *     )
   * )
   *
   * @OA\Response(
   *     response=404,
   *     description="Opening Hours not found"
   * )
   * @OA\Tag(name="opening hours")
   * @param $calendar_id
   *
   * @return View
   */
  public function getOpeningHoursAction($calendar_id)
  {
    $this->denyAccessUnlessGranted(
      BackofficeVoter::VIEW,
      CalendarsBackOffice::PATH,
      CalendarsBackOffice::IDENTIFIER . ' integration is not enabled on current tenant'
    );

    try {
      $repository = $this->em->getRepository('App\Entity\Calendar');
      $calendar = $repository->find($calendar_id);
      if ($calendar === null) {
        return $this->view(["Object not found"], Response::HTTP_NOT_FOUND);
      }
      return $this->view(['results' => $calendar->getOpeningHours(), 'count' => count($calendar->getOpeningHours())], Response::HTTP_OK);
    } catch (\Exception $e) {
      return $this->view(["Object not found"], Response::HTTP_NOT_FOUND);
    }
  }

  /**
   * Retrieve an Opening Hour of a Calendar
   * @Rest\Get("/{calendar_id}/opening-hours/{id}", name="opening-hour_api_get")
   *
   * @OA\Response(
   *     response=200,
   *     description="Retreive an Opening Hour of a Calendar",
   *     @OA\JsonContent(
   *         type="array",
   *         @OA\Items(ref=@Model(type=OpeningHour::class))
   *     )
   * )
   *
   * @OA\Response(
   *     response=404,
   *     description="Opening Hour not found"
   * )
   * @OA\Tag(name="opening hours")
   *
   * @param $calendar_id
   * @param $id
   *
   * @return View
   */
  public function getOpeningHourAction($calendar_id, $id)
  {
    $this->denyAccessUnlessGranted(
      BackofficeVoter::VIEW,
      CalendarsBackOffice::PATH,
      CalendarsBackOffice::IDENTIFIER . ' integration is not enabled on current tenant'
    );

    try {
      $repository = $this->em->getRepository('App\Entity\OpeningHour');
      $openingHour = $repository->findOneBy(['calendar' => $calendar_id, 'id' => $id]);

      if ($openingHour === null) {
        return $this->view(["Object not found"], Response::HTTP_NOT_FOUND);
      }
      return $this->view($openingHour, Response::HTTP_OK);
    } catch (\Exception $e) {
      return $this->view(["Object not found"], Response::HTTP_NOT_FOUND);
    }
  }

  /**
   * Delete an Opening Hour of a Calendar
   * @Rest\Delete("/{calendar_id}/opening-hours/{id}", name="opening-hour_api_delete")
   *
   * @Security(name="Bearer")
   *
   * @OA\Response(
   *     response=204,
   *     description="The resource was deleted successfully."
   * )
   *
   * @OA\Response(
   *     response=403,
   *     description="Access denied"
   * )
   *
   * @OA\Tag(name="opening hours")
   *
   * @param $calendar_id
   * @param $id
   *
   * @Method("DELETE")
   * @return View
   */
  public function deleteOpeningHourAction($calendar_id, $id)
  {
    $this->denyAccessUnlessGranted(
      BackofficeVoter::VIEW,
      CalendarsBackOffice::PATH,
      CalendarsBackOffice::IDENTIFIER . ' integration is not enabled on current tenant'
    );

    $repository = $this->em->getRepository('App\Entity\OpeningHour');
    $openingHour = $repository->findOneBy(['calendar' => $calendar_id, 'id' => $id]);
    if ($openingHour) {
      $this->denyAccessUnlessGranted(CalendarVoter::DELETE, $openingHour->getCalendar());

      $this->em->remove($openingHour);
      $this->em->flush();
    }
    return $this->view(null, Response::HTTP_NO_CONTENT);
  }


  /**
   * Create an Opening Hour
   * @Rest\Post("/{calendar_id}/opening-hours", name="opening-hour_api_post")
   *
   * @Security(name="Bearer")
   *
   * @OA\RequestBody(
   *     description="The Opening Hour to create",
   *     required=true,
   *     @OA\MediaType(
   *         mediaType="application/json",
   *         @OA\Schema(
   *             type="object",
   *             ref=@Model(type=OpeningHour::class)
   *         )
   *     )
   * )
   *
   * @OA\Response(
   *     response=201,
   *     description="Create an Opening Hour"
   * )
   *
   * @OA\Response(
   *     response=400,
   *     description="Bad request"
   * )
   *
   * @OA\Response(
   *     response=403,
   *     description="Access denied"
   * )
   *
   * @OA\Tag(name="opening hours")
   *
   * @param $calendar_id
   * @param Request $request
   *
   * @return View
   * @throws \Exception
   */

  public function postOpeningHourAction($calendar_id, Request $request)
  {
    $this->denyAccessUnlessGranted(
      BackofficeVoter::VIEW,
      CalendarsBackOffice::PATH,
      CalendarsBackOffice::IDENTIFIER . ' integration is not enabled on current tenant'
    );

    $calendar = $this->em->getRepository('App\Entity\Calendar')->find($calendar_id);
    if (!$calendar) {
      return $this->view('Calendar not found', Response::HTTP_BAD_REQUEST);
    }
    $this->denyAccessUnlessGranted(CalendarVoter::EDIT, $calendar);

    $openingHour = new OpeningHour();
    $openingHour->setCalendar($calendar);
    $form = $this->createForm('App\Form\OpeningHourType', $openingHour);
    $this->processForm($request, $form);

    if ($form->isSubmitted() && !$form->isValid()) {
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
        'description' => 'Contact technical support at support@opencontent.it'
      ];
      $this->logger->error(
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
   * @Security(name="Bearer")
   *
   * @OA\RequestBody(
   *     description="The opening hour to create",
   *     required=true,
   *     @OA\MediaType(
   *         mediaType="application/json",
   *         @OA\Schema(
   *             type="object",
   *             ref=@Model(type=OpeningHour::class)
   *         )
   *     )
   * )
   *
   * @OA\Response(
   *     response=200,
   *     description="Edit full opening hour"
   * )
   *
   * @OA\Response(
   *     response=400,
   *     description="Bad request"
   * )
   *
   * @OA\Response(
   *     response=403,
   *     description="Access denied"
   * )
   *
   * @OA\Response(
   *     response=404,
   *     description="Not found"
   * )
   * @OA\Tag(name="opening hours")
   *
   * @param Request $request
   * @param $calendar_id
   * @param $id
   *
   * @return View
   */
  public function putOpeningHourAction($calendar_id, $id, Request $request)
  {
    $this->denyAccessUnlessGranted(
      BackofficeVoter::VIEW,
      CalendarsBackOffice::PATH,
      CalendarsBackOffice::IDENTIFIER . ' integration is not enabled on current tenant'
    );

    $repository = $this->em->getRepository('App\Entity\OpeningHour');
    $openingHour = $repository->findOneBy(['calendar' => $calendar_id, 'id' => $id]);

    if (!$openingHour) {
      return $this->view(["Object not found"], Response::HTTP_NOT_FOUND);
    }

    $this->denyAccessUnlessGranted(CalendarVoter::EDIT, $openingHour->getCalendar());

    $form = $this->createForm('App\Form\OpeningHourType', $openingHour);
    $this->processForm($request, $form);

    if ($form->isSubmitted() && !$form->isValid()) {
      $errors = $this->getErrorsFromForm($form);
      $data = [
        'type' => 'put_validation_error',
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
        'description' => 'Contact technical support at support@opencontent.it'
      ];
      $this->logger->error(
        $e->getMessage(),
        ['request' => $request]
      );
      return $this->view($data, Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    return $this->view(["Object Modified Successfully"], Response::HTTP_OK);
  }

  /**
   * Patch a Opening Hour
   * @Rest\Patch("/{calendar_id}/opening-hours/{id}", name="opening-hour_api_patch")
   *
   * @Security(name="Bearer")
   *
   * @OA\RequestBody(
   *     description="The Opening Hour to patch",
   *     required=true,
   *     @OA\MediaType(
   *         mediaType="application/json",
   *         @OA\Schema(
   *             type="object",
   *             ref=@Model(type=OpeningHour::class)
   *         )
   *     )
   * )
   *
   * @OA\Response(
   *     response=200,
   *     description="Patch a  Opening Hour"
   * )
   *
   * @OA\Response(
   *     response=400,
   *     description="Bad request"
   * )
   *
   * @OA\Response(
   *     response=403,
   *     description="Access denied"
   * )
   *
   * @OA\Response(
   *     response=404,
   *     description="Not found"
   * )
   * @OA\Tag(name="opening hours")
   *
   * @param Request $request
   * @param $calendar_id
   * @param $id
   *
   * @return View
   */
  public function patchOpeningHourAction($calendar_id, $id, Request $request)
  {
    $this->denyAccessUnlessGranted(
      BackofficeVoter::VIEW,
      CalendarsBackOffice::PATH,
      CalendarsBackOffice::IDENTIFIER . ' integration is not enabled on current tenant'
    );

    $repository = $this->em->getRepository('App\Entity\OpeningHour');
    $openingHour = $repository->findOneBy(['calendar' => $calendar_id, 'id' => $id]);

    $openingHour->setDaysOfWeek([]);

    if (!$openingHour) {
      return $this->view(["Object not found"], Response::HTTP_NOT_FOUND);
    }

    $this->denyAccessUnlessGranted(CalendarVoter::EDIT, $openingHour->getCalendar());

    $form = $this->createForm('App\Form\OpeningHourType', $openingHour);
    $this->processForm($request, $form);

    if ($form->isSubmitted() && !$form->isValid()) {
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
        'description' => 'Contact technical support at support@opencontent.it'
      ];
      $this->logger->error(
        $e->getMessage(),
        ['request' => $request]
      );
      return $this->view($data, Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    return $this->view(["Object Patched Successfully"], Response::HTTP_OK);
  }

  /**
   * Retreive a Calendar opening hours overlaps
   * @Rest\Get("/{id}/overlaps", name="calendar-overlaps_api_get")
   *
   *
   *
   * @OA\Parameter(
   *      name="opening_hours",
   *      in="query",
   *      @OA\Schema(
   *          type="string"
   *      ),
   *      required=false,
   *      description="Get overlaps related to selected opening hours"
   *  )
   *
   * @OA\Response(
   *     response=200,
   *     description="Retreive Calendar's opening hours overlaps",
   * )
   *
   * @OA\Response(
   *     response=404,
   *     description="Calendar not found"
   * )
   * @OA\Tag(name="calendars")
   *
   * @param $id
   * @param Request $request
   * @return View
   */
  public function getCalendarOverlapsAction($id, Request $request)
  {
    $selectedOpeningHours = $request->query->get('opening_hours');
    if ($selectedOpeningHours) {
      $selectedOpeningHours = explode(',', $selectedOpeningHours);
    }

    try {
      $calendar = $this->em->getRepository('App\Entity\Calendar')->findOneBy(['id' => $id]);
      if ( $calendar === null) {
        return $this->view(["Object not found"], Response::HTTP_NOT_FOUND);
      }

      $overlaps = $this->meetingService->getOpeningHoursOverlaps($calendar, $selectedOpeningHours);
      return $this->view([
        "overlaps" => $overlaps,
        "count"=>count($overlaps)
      ], Response::HTTP_OK);
    } catch (\Exception $e) {
      return $this->view(["Object not found"], Response::HTTP_NOT_FOUND);
    }
  }
}
