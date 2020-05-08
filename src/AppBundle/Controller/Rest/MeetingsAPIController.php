<?php

namespace AppBundle\Controller\Rest;

use AppBundle\Entity\CPSUser;
use AppBundle\Entity\Meeting;
use AppBundle\Entity\User;
use AppBundle\Services\InstanceService;
use Doctrine\ORM\EntityManager;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use Symfony\Component\Form\FormInterface;
use Nelmio\ApiDocBundle\Annotation\Model;
use Swagger\Annotations as SWG;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class MeetingsAPIController
 * @property EntityManager em
 * @property InstanceService is
 * @package AppBundle\Controller
 * @Route("/meetings")
 */
class MeetingsAPIController extends AbstractFOSRestController
{
  const CURRENT_API_VERSION = '1.0';

  private $em;

  private $is;

  /**
   * @var TranslatorInterface $translator
   */
  private $translator;

  public function __construct(TranslatorInterface $translator, EntityManager $em, InstanceService $is)
  {
    $this->translator = $translator;
    $this->em = $em;
    $this->is = $is;
  }


  /**
   * List all Meetings
   * @Rest\Get("", name="meetings_api_list")
   *
   * @SWG\Response(
   *     response=200,
   *     description="Retrieve list of meetings",
   *     @SWG\Schema(
   *         type="array",
   *         @SWG\Items(ref=@Model(type=Meeting::class))
   *     )
   * )
   * @SWG\Tag(name="meetings")
   */
  public function getMeetingsAction()
  {
    /** @var User $user */
    $user = $this->getUser();

    $builder = $this->em->createQueryBuilder();
    $builder
      ->select('meeting.id')
      ->from(Meeting::class, 'meeting')
      ->leftJoin('meeting.calendar', 'calendar')
      ->leftJoin('calendar.owner', 'owner')
      ->leftJoin('calendar.moderators', 'moderators')
      ->where('calendar.owner = :owner')
      ->Orwhere('moderators.id = :operatore')
      ->setParameter('operatore', $user)
      ->setParameter('owner', $user);

    $results = $builder->getQuery()->getResult();
    $meetings = array();

    foreach ($results as $result) {
      $meetings[] = $this->getDoctrine()->getRepository('AppBundle:Meeting')->find($result);
    }

    return $this->view($meetings, Response::HTTP_OK);
  }

  /**
   * Retreive a Meeting
   * @Rest\Get("/{id}", name="meeting_api_get")
   *
   * @SWG\Response(
   *     response=200,
   *     description="Retreive a Meeting",
   *     @Model(type=Meeting::class)
   * )
   *
   * @SWG\Response(
   *     response=404,
   *     description="Meeting not found"
   * )
   * @SWG\Tag(name="meetings")
   *
   * @param $id
   * @return View
   */
  public function getMeetingAction($id)
  {
    try {
      /** @var User $user */
      $user = $this->getUser();

      $builder = $this->em->createQueryBuilder();
      $builder
        ->select('meeting.id')
        ->from(Meeting::class, 'meeting')
        ->leftJoin('meeting.calendar', 'calendar')
        ->leftJoin('calendar.owner', 'owner')
        ->leftJoin('calendar.moderators', 'moderators')
        ->where('calendar.owner = :owner')
        ->Orwhere('moderators.id = :operatore')
        ->andWhere('meeting.id = :meeting_id')
        ->setParameter('meeting_id', $id)
        ->setParameter('operatore', $user)
        ->setParameter('owner', $user);

      $result = $builder->getQuery()->getOneOrNullResult();

      if ($result) {
        $meeting = $this->getDoctrine()->getRepository('AppBundle:Meeting')->find($result['id']);
        return $this->view($meeting, Response::HTTP_OK);
      }
      return $this->view("Object not found", Response::HTTP_NOT_FOUND);
    } catch (\Exception $e) {
      return $this->view("Object not found", Response::HTTP_NOT_FOUND);
    }
  }

  /**
   * Create a Meeting
   * @Rest\Post(name="meetings_api_post")
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
   *     name="Meeting",
   *     in="body",
   *     type="json",
   *     description="The meeting to create",
   *     required=true,
   *     @SWG\Schema(
   *         type="object",
   *         ref=@Model(type=Meeting::class)
   *     )
   * )
   *
   * @SWG\Response(
   *     response=201,
   *     description="Create a Meeting"
   * )
   *
   * @SWG\Response(
   *     response=400,
   *     description="Bad request"
   * )
   * @SWG\Tag(name="meetings")
   *
   * @param Request $request
   * @return View
   * @throws \Exception
   */
  public function postMeetingAction(Request $request)
  {
    $meeting = new Meeting();

    $form = $this->createForm('AppBundle\Form\MeetingType', $meeting);
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

        $em->persist($user);
        $meeting->setUser($user);
      } else if ($meeting->getFiscalCode() && !$meeting->getUser()) {
        $result = $em->createQueryBuilder()
          ->select('user.id')
          ->from('AppBundle:User', 'user')
          ->where('upper(user.username) = upper(:username)')
          ->setParameter('username', $meeting->getFiscalCode())
          ->getQuery()->getResult();
        if ( !empty($result)) {
          $repository = $em->getRepository('AppBundle:CPSUser');
          /**
           * @var CPSUser $user
           */
          $user =  $repository->find($result[0]['id']);
        } else {
          $user = null;
        }
        if (!$user) {
          $user = new CPSUser();
          $user->setEmail($meeting->getEmail() ? $meeting->getEmail() : '');
          $user->setEmailContatto($meeting->getEmail() ? $meeting->getEmail() : '');
          $user->setNome($meeting->getName() ? $meeting->getName() : '');
          $user->setCognome('');
          $user->setCodiceFiscale($meeting->getFiscalCode());
          $user->setUsername($meeting->getFiscalCode());

          $user->addRole('ROLE_USER')
            ->addRole('ROLE_CPS_USER')
            ->setEnabled(true)
            ->setPassword('');

          $em->persist($user);
          $meeting->setUser($user);
        } else {
          $meeting->setUser($user);
        }
      }
      $em->persist($meeting);
      $em->flush();

      if ($meeting->getUser()->getEmail())
        $this->addFlash('feedback', $this->translator->trans('meetings.email.success'));
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

    return $this->view($meeting, Response::HTTP_CREATED);
  }

  /**
   * Edit full Meeting
   * @Rest\Put("/{id}", name="meetings_api_put")
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
   *     name="Meeting",
   *     in="body",
   *     type="json",
   *     description="The meeting to edit",
   *     required=true,
   *     @SWG\Schema(
   *         type="object",
   *         ref=@Model(type=Meeting::class)
   *     )
   * )
   *
   * @SWG\Response(
   *     response=200,
   *     description="Edit full Meeting"
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
   * @SWG\Tag(name="meetings")
   *
   * @param $id
   * @param Request $request
   * @return View
   */
  public function putMeetingAction($id, Request $request)
  {
    $repository = $this->getDoctrine()->getRepository('AppBundle:Meeting');
    $meeting = $repository->find($id);
    $oldMeeting = clone $meeting;

    if (!$meeting) {
      return $this->view("Object not found", Response::HTTP_NOT_FOUND);
    }
    $form = $this->createForm('AppBundle\Form\MeetingType', $meeting);
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
      $dateChanged = $oldMeeting->getFromTime() != $meeting->getFromTime();

      $em = $this->getDoctrine()->getManager();
      // Auto approve meeting when changing date
      if ($dateChanged && $oldMeeting->getStatus() == Meeting::STATUS_PENDING) {
        $meeting->setStatus(Meeting::STATUS_APPROVED);
      }

      $em->persist($meeting);
      $em->flush();

      if ($meeting->getUser()->getEmail())
        $this->addFlash('feedback', $this->translator->trans('meetings.email.success'));
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
   * Patch a Meeting
   * @Rest\Patch("/{id}", name="meetings_api_patch")
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
   *     name="Meeting",
   *     in="body",
   *     type="json",
   *     description="The meeting to patch",
   *     required=true,
   *     @SWG\Schema(
   *         type="object",
   *         ref=@Model(type=Meeting::class)
   *     )
   * )
   *
   * @SWG\Response(
   *     response=200,
   *     description="Patch a Meeting"
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
   * @SWG\Tag(name="meetings")
   *
   * @param $id
   * @param Request $request
   * @return View
   */
  public function patchMeetingAction($id, Request $request)
  {

    $repository = $this->getDoctrine()->getRepository('AppBundle:Meeting');
    $meeting = $repository->find($id);
    $oldMeeting = clone $meeting;

    if (!$meeting) {
      return $this->view("Object not found", Response::HTTP_NOT_FOUND);
    }
    $form = $this->createForm('AppBundle\Form\MeetingType', $meeting);
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
      $dateChanged = $oldMeeting->getFromTime() != $meeting->getFromTime();

      $em = $this->getDoctrine()->getManager();
      // Auto approve meeting when changing date
      if ($dateChanged && $oldMeeting->getStatus() == Meeting::STATUS_PENDING) {
        $meeting->setStatus(Meeting::STATUS_APPROVED);
      }

      $em->persist($meeting);
      $em->flush();

      if ($meeting->getUser()->getEmail())
        $this->addFlash('feedback', $this->translator->trans('meetings.email.success'));

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

    return $this->view("Object Patched Successfully", Response::HTTP_OK);
  }

  /**
   * Delete a Meeting
   * @Rest\Delete("/{id}", name="meetings_api_delete")
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
   * @SWG\Tag(name="meetings")
   *
   * @Method("DELETE")
   * @param $id
   * @return View
   */
  public function deleteAction($id)
  {
    $meeting = $this->getDoctrine()->getRepository('AppBundle:Meeting')->find($id);
    if ($meeting) {
      // debated point: should we 404 on an unknown nickname?
      // or should we just return a nice 204 in all cases?
      // we're doing the latter
      $em = $this->getDoctrine()->getManager();
      try {
        $em->remove($meeting);
        $em->flush();

        $this->addFlash('feedback', $this->translator->trans('meetings.email.success'));

      } catch (\Exception $e) {
        return $this->view("There was an error during delete process", Response::HTTP_NOT_FOUND);
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

}
