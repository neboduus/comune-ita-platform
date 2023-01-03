<?php

namespace App\Controller\Rest;

use App\BackOffice\CalendarsBackOffice;
use App\Entity\CPSUser;
use App\Entity\Meeting;
use App\Entity\OperatoreUser;
use App\Entity\User;
use App\Security\Voters\BackofficeVoter;
use App\Security\Voters\MeetingVoter;
use App\Services\InstanceService;
use App\Services\MeetingService;
use App\Utils\FormUtils;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\Form\FormInterface;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use OpenApi\Annotations as OA;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Validator\Exception\ValidatorException;

/**
 * Class MeetingsAPIController
 * @property EntityManagerInterface em
 * @property InstanceService is
 * @package App\Controller
 * @Route("/meetings")
 */
class MeetingsAPIController extends AbstractFOSRestController
{
  const CURRENT_API_VERSION = '1.0';

  private $em;

  /**
   * @var TranslatorInterface $translator
   */
  private $translator;

  /** @var LoggerInterface */
  private $logger;

  /**
   * @var MeetingService
   */
  private $meetingService;

  /**
   * @param TranslatorInterface $translator
   * @param EntityManagerInterface $em
   * @param LoggerInterface $logger
   * @param MeetingService $meetingService
   */
  public function __construct(TranslatorInterface $translator, EntityManagerInterface $em, LoggerInterface $logger, MeetingService $meetingService)
  {
    $this->translator = $translator;
    $this->em = $em;
    $this->logger = $logger;
    $this->meetingService = $meetingService;
  }


  /**
   * List all Meetings
   * @Rest\Get("", name="meetings_api_list")
   *
   * @OA\Response(
   *     response=200,
   *     description="Retrieve list of meetings",
   *     @OA\JsonContent(
   *         type="array",
   *         @OA\Items(ref=@Model(type=Meeting::class, groups={"read"}))
   *     )
   * )
   *
   * @OA\Response(
   *     response=403,
   *     description="Access denied"
   * )
   *
   * @OA\Tag(name="meetings")
   */
  public function getMeetingsAction(Request $request)
  {
    $this->denyAccessUnlessGranted(
      BackofficeVoter::VIEW,
      CalendarsBackOffice::PATH,
      CalendarsBackOffice::IDENTIFIER . ' integration is not enabled on current tenant'
    );

    /** @var User $user */
    $user = $this->getUser();
    $statusParameter = $request->get('status', false);


    $builder = $this->em->createQueryBuilder();
    $builder
      ->select('meeting')
      ->from(Meeting::class, 'meeting')
      ->leftJoin('meeting.calendar', 'calendar');

    if ($user instanceof CPSUser) {
      $builder
        ->where('meeting.user = :user')
        ->setParameter('user', $user);
    } elseif ($user instanceof OperatoreUser) {
      $builder
        ->where(':user MEMBER OF calendar.moderators or calendar.owner = :user')
        ->setParameter('user', $user);
    }

    $meetingStatuses = array_keys(Meeting::getStatuses());
    if (!in_array($statusParameter, $meetingStatuses)) {
      return $this->view(
        ["Status code not present, chose one between: " . implode(',', $meetingStatuses)],
        Response::HTTP_BAD_REQUEST
      );
    }

    if ($statusParameter) {
      $builder
        ->where('meeting.status = :status')
        ->setParameter('status', $statusParameter);
    }

    $results = $builder->getQuery()->getResult();
    return $this->view($results, Response::HTTP_OK);
  }

  /**
   * Retrieve a Meeting
   * @Rest\Get("/{id}", name="meeting_api_get")
   *
   * @OA\Response(
   *     response=200,
   *     description="Retrieve a Meeting",
   *     @Model(type=Meeting::class, groups={"read"})
   * )
   *
   * @OA\Response(
   *     response=403,
   *     description="Access denied"
   * )
   *
   * @OA\Response(
   *     response=404,
   *     description="Meeting not found"
   * )
   * @OA\Tag(name="meetings")
   *
   * @param $id
   * @return View
   */
  public function getMeetingAction($id)
  {
    $this->denyAccessUnlessGranted(
      BackofficeVoter::VIEW,
      CalendarsBackOffice::PATH,
      CalendarsBackOffice::IDENTIFIER . ' integration is not enabled on current tenant'
    );

    try {
      $builder = $this->em->createQueryBuilder();
      $builder
        ->select('meeting.id')
        ->from(Meeting::class, 'meeting')
        ->where('meeting.id = :meeting_id')
        ->setParameter('meeting_id', $id);

      $result = $builder->getQuery()->getOneOrNullResult();

      if ($result) {
        $meeting = $this->getDoctrine()->getRepository('App\Entity\Meeting')->find($result['id']);

        $this->denyAccessUnlessGranted(MeetingVoter::VIEW, $meeting);

        return $this->view($meeting, Response::HTTP_OK);
      }
      return $this->view(["Object not found"], Response::HTTP_NOT_FOUND);
    } catch (\Exception $e) {
      return $this->view(["Object not found"], Response::HTTP_NOT_FOUND);
    }
  }

  /**
   * Create a Meeting
   * @Rest\Post(name="meetings_api_post")
   *
   * @Security(name="Bearer")
   *
   * @OA\RequestBody(
   *     description="The meeting to create",
   *     required=true,
   *     @OA\MediaType(
   *         mediaType="application/json",
   *         @OA\Schema(
   *             type="object",
   *             ref=@Model(type=Meeting::class, groups={"write"})
   *         )
   *     )
   * )
   *
   * @OA\Response(
   *     response=201,
   *     description="Create a Meeting"
   * )
   *
   * @OA\Response(
   *     response=400,
   *     description="Bad request"
   * )
   * @OA\Tag(name="meetings")
   *
   * @param Request $request
   * @return View
   * @throws \Exception
   */
  public function postMeetingAction(Request $request)
  {
    $this->denyAccessUnlessGranted(
      BackofficeVoter::VIEW,
      CalendarsBackOffice::PATH,
      CalendarsBackOffice::IDENTIFIER . ' integration is not enabled on current tenant'
    );

    $meeting = new Meeting();

    $form = $this->createForm('App\Form\MeetingType', $meeting);
    $this->processForm($request, $form);
    if ($form->isSubmitted() && !$form->isValid()) {
      $errors = FormUtils::getErrorsFromForm($form);
      $data = [
        'type' => 'validation_error',
        'title' => 'There was a validation error',
        'errors' => $errors
      ];
      return $this->view($data, Response::HTTP_BAD_REQUEST);
    }

    try {
      if (!$meeting->getFiscalCode() && !$meeting->getUser()) {
        // codice fiscale non fornito
        $user = new CPSUser();
        $user->setEmail($meeting->getEmail() ? $meeting->getEmail() : '');
        $user->setEmailContatto($meeting->getEmail() ? $meeting->getEmail() : '');
        $user->setNome($meeting->getName() ? $meeting->getName() : '');
        $user->setCognome('');
        $user->setCodiceFiscale($user->getId() . '-' . time());
        $user->setUsername($user->getId());

        $user->addRole('ROLE_USER')
          ->addRole('ROLE_CPS_USER')
          ->setEnabled(true)
          ->setPassword('');

        $this->em->persist($user);
        $meeting->setUser($user);
      } else if ($meeting->getFiscalCode() && !$meeting->getUser()) {
        $result = $this->em->createQueryBuilder()
          ->select('user.id')
          ->from('App:User', 'user')
          ->where('upper(user.username) = upper(:username)')
          ->setParameter('username', $meeting->getFiscalCode())
          ->getQuery()->getResult();
        if (!empty($result)) {
          $repository = $this->em->getRepository('App\Entity\CPSUser');
          /**
           * @var CPSUser $user
           */
          $user = $repository->find($result[0]['id']);
        } else {
          $user = null;
        }
        if (!$user) {
          $user = new CPSUser();
          $user->setEmail($meeting->getEmail() ?: '');
          $user->setEmailContatto($meeting->getEmail() ?: '');
          $user->setNome($meeting->getName() ?: '');
          $user->setCognome('');
          $user->setCodiceFiscale($meeting->getFiscalCode());
          $user->setUsername($meeting->getFiscalCode());

          $user->addRole('ROLE_USER')
            ->addRole('ROLE_CPS_USER')
            ->setEnabled(true)
            ->setPassword('');

          $this->em->persist($user);
          $meeting->setUser($user);
        } else {
          $meeting->setUser($user);
        }
      }

      $this->meetingService->save($meeting, false);
      $this->em->flush();

      if ($meeting->getUser()->getEmail())
        $this->addFlash('feedback', $this->translator->trans('meetings.email.success'));
    } catch (ValidatorException $e) {
      $data = [
        'type' => 'error',
        'title' => $e->getMessage(),
        'description' => $e->getMessage(),
      ];
      $this->logger->error(
        $e->getMessage(),
        ['request' => $request]
      );
      return $this->view($data, Response::HTTP_BAD_REQUEST);
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

    return $this->view($meeting, Response::HTTP_CREATED);
  }

  /**
   * Edit full Meeting
   * @Rest\Put("/{id}", name="meetings_api_put")
   *
   * @Security(name="Bearer")
   *
   * @OA\RequestBody(
   *     description="The meeting to edit",
   *     required=true,
   *     @OA\MediaType(
   *         mediaType="application/json",
   *         @OA\Schema(
   *             type="object",
   *             ref=@Model(type=Meeting::class, groups={"write"})
   *         )
   *     )
   * )
   *
   * @OA\Response(
   *     response=200,
   *     description="Edit full Meeting"
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
   * @OA\Tag(name="meetings")
   *
   * @param $id
   * @param Request $request
   * @return View
   */
  public function putMeetingAction($id, Request $request)
  {
    $this->denyAccessUnlessGranted(
      BackofficeVoter::VIEW,
      CalendarsBackOffice::PATH,
      CalendarsBackOffice::IDENTIFIER . ' integration is not enabled on current tenant'
    );

    $repository = $this->getDoctrine()->getRepository('App\Entity\Meeting');
    $meeting = $repository->find($id);

    $this->denyAccessUnlessGranted(MeetingVoter::EDIT, $meeting);

    $oldMeeting = clone $meeting;

    if (!$meeting) {
      return $this->view(["Object not found"], Response::HTTP_NOT_FOUND);
    }
    $form = $this->createForm('App\Form\MeetingType', $meeting);
    $this->processForm($request, $form);

    if ($form->isSubmitted() && !$form->isValid()) {
      $errors = FormUtils::getErrorsFromForm($form);
      $data = [
        'type' => 'put_validation_error',
        'title' => 'There was a validation error',
        'errors' => $errors
      ];
      return $this->view($data, Response::HTTP_BAD_REQUEST);
    }

    try {
      $dateChanged = $oldMeeting->getFromTime() != $meeting->getFromTime();

      // Auto approve meeting when changing date
      if ($dateChanged && $oldMeeting->getStatus() == Meeting::STATUS_PENDING) {
        $meeting->setStatus(Meeting::STATUS_APPROVED);
      }

      $this->meetingService->save($meeting);

      if ($meeting->getUser()->getEmail())
        $this->addFlash('feedback', $this->translator->trans('meetings.email.success'));
    } catch (ValidatorException $e) {
      $data = [
        'type' => 'error',
        'title' => $e->getMessage(),
        'description' => $e->getMessage(),
      ];
      $this->logger->error(
        $e->getMessage(),
        ['request' => $request]
      );
      return $this->view($data, Response::HTTP_BAD_REQUEST);
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
   * Patch a Meeting
   * @Rest\Patch("/{id}", name="meetings_api_patch")
   *
   * @Security(name="Bearer")
   *
   * @OA\RequestBody(
   *     description="The meeting to patch",
   *     required=true,
   *     @OA\MediaType(
   *         mediaType="application/json",
   *         @OA\Schema(
   *             type="object",
   *             ref=@Model(type=Meeting::class, groups={"write"})
   *         )
   *     )
   * )
   *
   * @OA\Response(
   *     response=200,
   *     description="Patch a Meeting"
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
   * @OA\Tag(name="meetings")
   *
   * @param $id
   * @param Request $request
   * @return View
   */
  public function patchMeetingAction($id, Request $request)
  {
    $this->denyAccessUnlessGranted(
      BackofficeVoter::VIEW,
      CalendarsBackOffice::PATH,
      CalendarsBackOffice::IDENTIFIER . ' integration is not enabled on current tenant'
    );

    $repository = $this->getDoctrine()->getRepository('App\Entity\Meeting');
    $meeting = $repository->find($id);
    $oldMeeting = clone $meeting;

    if (!$meeting) {
      return $this->view(["Object not found"], Response::HTTP_NOT_FOUND);
    }

    $this->denyAccessUnlessGranted(MeetingVoter::EDIT, $meeting);

    $form = $this->createForm('App\Form\MeetingType', $meeting);
    $this->processForm($request, $form);

    if ($form->isSubmitted() && !$form->isValid()) {
      $errors = FormUtils::getErrorsFromForm($form);
      $data = [
        'type' => 'validation_error',
        'title' => 'There was a validation error',
        'errors' => $errors
      ];
      return $this->view($data, Response::HTTP_BAD_REQUEST);
    }

    try {
      $dateChanged = $oldMeeting->getFromTime() != $meeting->getFromTime();

      // Auto approve meeting when changing date
      if ($dateChanged && $oldMeeting->getStatus() == Meeting::STATUS_PENDING) {
        $meeting->setStatus(Meeting::STATUS_APPROVED);
      }

      $this->meetingService->save($meeting);

      if ($meeting->getUser()->getEmail())
        $this->addFlash('feedback', $this->translator->trans('meetings.email.success'));

    } catch (ValidatorException $e) {
      $data = [
        'type' => 'error',
        'title' => $e->getMessage(),
        'description' => $e->getMessage(),
      ];
      $this->logger->error(
        $e->getMessage(),
        ['request' => $request]
      );
      return $this->view($data, Response::HTTP_BAD_REQUEST);
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
   * Delete a Meeting
   * @Rest\Delete("/{id}", name="meetings_api_delete")
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
   * @OA\Tag(name="meetings")
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

    $meeting = $this->getDoctrine()->getRepository('App\Entity\Meeting')->find($id);
    if ($meeting) {
      $this->denyAccessUnlessGranted(MeetingVoter::DELETE, $meeting);
      // debated point: should we 404 on an unknown nickname?
      // or should we just return a nice 204 in all cases?
      // we're doing the latter
      $em = $this->getDoctrine()->getManager();
      try {
        $this->em->remove($meeting);
        $this->em->flush();

        $this->addFlash('feedback', $this->translator->trans('meetings.email.success'));

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
}
