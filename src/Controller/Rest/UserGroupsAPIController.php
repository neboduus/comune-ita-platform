<?php

namespace App\Controller\Rest;

use App\Entity\Categoria;
use App\Entity\OperatoreUser;
use App\Entity\UserGroup;
use App\Utils\FormUtils;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\View\View;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use FOS\RestBundle\Controller\Annotations as Rest;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use OpenApi\Annotations as OA;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class AbstractFOSRestController
 * @Route("/user-groups")
 */
class UserGroupsAPIController extends AbstractFOSRestController
{

  private EntityManagerInterface $entityManager;

  private LoggerInterface $logger;

  /**
   * @param EntityManagerInterface $entityManager
   * @param LoggerInterface $logger
   */
  public function __construct(EntityManagerInterface $entityManager, LoggerInterface $logger)
  {
    $this->entityManager = $entityManager;
    $this->logger = $logger;
  }

  /**
   * List all user groups
   * @Rest\Get("", name="user_group_api_list")
   *
   * @OA\Parameter(
   *      name="x-locale",
   *      in="header",
   *      description="Request locale",
   *      required=false  ,
   *      @OA\Schema(type="string")
   *  )
   *
   * @OA\Parameter(
   *      name="has_calendar",
   *      in="query",
   *      @OA\Schema(type="boolean"),
   *      required=false,
   *      description="If true retreive only user groups that have calendars, if false only user groups that not have calendars"
   *  )
   *
   * @OA\Parameter(
   *      name="service_id",
   *      in="query",
   *      @OA\Schema(type="string"),
   *      required=false,
   *      description="Uuid of the service"
   *  )
   *
   * @OA\Response(
   *     response=200,
   *     description="Retrieve list of user groups",
   *     @OA\JsonContent(
   *         type="array",
   *         @OA\Items(ref=@Model(type=UserGroup::class, groups={"read"}))
   *     )
   * )
   *
   * @OA\Tag(name="user-groups")
   * @param Request $request
   * @return View
   */
  public function getUserGroupsAction(Request $request): View
  {
    $criteria = [];
    if ($request->query->has('has_calendar')) {
      $criteria['has_calendar'] = $request->query->getBoolean('has_calendar');
    }

    $serviceId = $request->get('service_id', false);
    if ($serviceId) {
      if (!Uuid::isValid($serviceId)) {
        return $this->view('Parameter service_id is not a valid uuid');
      }
      $criteria['service_id'] = $serviceId;
    }

    $repo =  $this->entityManager->getRepository('App\Entity\UserGroup');
    $result = $repo->findByCriteria($criteria);

    return $this->view($result, Response::HTTP_OK);
  }


  /**
   * Retrieve a user group by id
   * @Rest\Get("/{id}", name="user_group_api_get")
   *
   * @OA\Parameter(
   *      name="x-locale",
   *      in="header",
   *      description="Request locale",
   *      required=false,
   *      @OA\Schema(
   *           type="string"
   *      )
   *  )
   *
   * @OA\Response(
   *     response=200,
   *     description="Retrieve a UserGroup",
   *     @Model(type=UserGroup::class, groups={"read"})
   * )
   *
   * @OA\Response(
   *     response=404,
   *     description="UserGroup not found"
   * )
   * @OA\Tag(name="user-groups")
   *
   * @param Request $request
   * @param string $id
   * @return View
   */
  public function getUserGroupAction(Request $request, $id): View
  {
    try {
      $repository = $this->getDoctrine()->getRepository('App\Entity\UserGroup');
      $result = $repository->find($id);
    } catch (\Exception $e) {
      return $this->view(["Object not found"], Response::HTTP_NOT_FOUND);
    }

    if ($result === null) {
      return $this->view(["Object not found"], Response::HTTP_NOT_FOUND);
    }

    return $this->view($result, Response::HTTP_OK);
  }


  /**
   * Create a UserGroup
   * @Rest\Post(name="user_group_api_post")
   *
   * @Security(name="Bearer")
   *
   * @OA\Parameter(
   *      name="x-locale",
   *      in="header",
   *      description="Request locale",
   *      required=false  ,
   *      @OA\Schema(
   *           type="string"
   *      )
   *  )
   *
   * @OA\RequestBody(
   *     description="The user group to create",
   *     required=true,
   *     @OA\MediaType(
   *         mediaType="application/json",
   *         @OA\Schema(
   *             type="object",
   *             ref=@Model(type=UserGroup::class, groups={"write"})
   *         )
   *     )
   * )
   *
   * @OA\Response(
   *     response=201,
   *     description="Create a User Group"
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
   * @OA\Tag(name="user-groups")
   *
   * @param Request $request
   * @return View
   */
  public function postUserGroupAction(Request $request): View
  {
    $this->denyAccessUnlessGranted(['ROLE_ADMIN']);

    $userGroup = new UserGroup();
    $form = $this->createForm('App\Form\Api\UserGroupApiType', $userGroup);

    $this->processForm($request, $form);
    if ($form->isSubmitted() && !$form->isValid()) {
      $errors = FormUtils::getErrorsFromForm($form);
      $data = [
        'type' => 'validation_error',
        'title' => 'There was a validation error',
        'errors' => $errors,
      ];
      return $this->view($data, Response::HTTP_BAD_REQUEST);
    }

    try {
      $this->checkRelations($userGroup, $request);
      $this->entityManager->persist($userGroup);
      $this->entityManager->flush();
    } catch (\Exception $e) {
      return $this->generateExceptionResponse($e, $request);
    }

    return $this->view($userGroup, Response::HTTP_CREATED);
  }




  /**
   * Edit full user group
   * @Rest\Put("/{id}", name="user_group_api_put")
   *
   * @Security(name="Bearer")
   *
   * @OA\Parameter(
   *      name="x-locale",
   *      in="header",
   *      description="Request locale",
   *      required=false,
   *      @OA\Schema(
   *           type="string"
   *      )
   *  )
   *
   * @OA\RequestBody(
   *     description="The recipient to update",
   *     required=true,
   *     @OA\MediaType(
   *         mediaType="application/json",
   *         @OA\Schema(
   *             type="object",
   *             ref=@Model(type=UserGroup::class, groups={"write"})
   *         )
   *     )
   * )
   *
   * @OA\Response(
   *     response=200,
   *     description="Edit full User Group"
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
   * @OA\Tag(name="user-groups")
   *
   * @param $id
   * @param Request $request
   * @return View
   */
  public function putUserGroupAction($id, Request $request): View
  {

    $this->denyAccessUnlessGranted(['ROLE_ADMIN' ]);

    $repository = $this->getDoctrine()->getRepository('App\Entity\UserGroup');
    $userGroup = $repository->find($id);

    if (!$userGroup) {
      return $this->view(["Object not found"], Response::HTTP_NOT_FOUND);
    }

    $form = $this->createForm('App\Form\Api\UserGroupApiType', $userGroup);
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
      $this->checkRelations($userGroup, $request);
      $this->entityManager->persist($userGroup);
      $this->entityManager->flush();
    } catch (\Exception $e) {
      return $this->generateExceptionResponse($e, $request);
    }

    return $this->view(["Object Modified Successfully"], Response::HTTP_OK);
  }


  /**
   * Patch a user group
   * @Rest\Patch("/{id}", name="user_group_api_patch")
   *
   * @Security(name="Bearer")
   *
   * @OA\Parameter(
   *      name="x-locale",
   *      in="header",
   *      description="Request locale",
   *      required=false,
   *      @OA\Schema(
   *           type="string"
   *      )
   *  )
   *
   * @OA\RequestBody(
   *     description="The recipient to update",
   *     required=true,
   *     @OA\MediaType(
   *         mediaType="application/json",
   *         @OA\Schema(
   *             type="object",
   *             ref=@Model(type=UserGroup::class, groups={"write"})
   *         )
   *     )
   * )
   *
   * @OA\Response(
   *     response=200,
   *     description="Patch a User Group"
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
   * @OA\Tag(name="user-groups")
   *
   * @param $id
   * @param Request $request
   * @return View
   */
  public function patchUserGroupAction($id, Request $request)
  {

    $this->denyAccessUnlessGranted(['ROLE_ADMIN' ]);

    $repository = $this->getDoctrine()->getRepository('App\Entity\UserGroup');
    $userGroup = $repository->find($id);

    if (!$userGroup) {
      return $this->view(["Object not found"], Response::HTTP_NOT_FOUND);
    }

    $form = $this->createForm('App\Form\Api\UserGroupApiType', $userGroup);
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
      $this->checkRelations($userGroup, $request);
      $this->entityManager->persist($userGroup);
      $this->entityManager->flush();
    } catch (\Exception $e) {
      return $this->generateExceptionResponse($e, $request);
    }

    return $this->view(["Object Patched Successfully"], Response::HTTP_OK);
  }


  /**
   * Delete a user group
   * @Rest\Delete("/{id}", name="user_group_api_delete")
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
   * @OA\Tag(name="user-groups")
   *
   * @param $id
   * @return View
   */
  public function deleteUserGroupAction($id): View
  {
    $this->denyAccessUnlessGranted(['ROLE_ADMIN' ]);
    $item = $this->getDoctrine()->getRepository('App\Entity\UserGroup')->find($id);
    if ($item) {
      $this->entityManager->remove($item);
      $this->entityManager->flush();
    }
    return $this->view(null, Response::HTTP_NO_CONTENT);
  }

  /**
   * @param Request $request
   * @param FormInterface $form
   * @return void
   */
  private function processForm(Request $request, FormInterface $form): void
  {
    $data = json_decode($request->getContent(), true);

    $clearMissing = $request->getMethod() != 'PATCH';
    $form->submit($data, $clearMissing);
  }

  /**
   * @param UserGroup $userGroup
   * @param Request $request
   * @return void
   */
  private function checkRelations(UserGroup &$userGroup, Request $request): void
  {

    if ($request->request->has('topic_id') && $request->request->get('topic_id')) {
      $category = $this->entityManager->getRepository('App\Entity\Categoria')->find($request->request->get('topic_id'));
      if (!$category instanceof Categoria) {
        throw new InvalidArgumentException("Category does not exist");
      }
      $userGroup->setTopic($category);
    }

    if ($request->request->has('manager_id') && $request->request->get('manager_id')) {
      $manager = $this->entityManager->getRepository('App\Entity\OperatoreUser')->find($request->request->get('manager_id'));
      if (!$manager instanceof OperatoreUser) {
        throw new InvalidArgumentException("Manager does not exist");
      }
      $userGroup->setManager($manager);
    }
  }

  private function generateExceptionResponse(\Exception $e, Request $request): View
  {
    if  ( $e instanceof InvalidArgumentException ) {
      $data = [
        'type' => 'validation_error',
        'title' => 'There was a validation error',
        'errors' => $e->getMessage(),
      ];
    } else {
      $data = [
        'type' => 'error',
        'title' => 'There was an error during save process',
        'description' => 'Contact technical support at support@opencontent.it'
      ];
      $this->logger->error($e->getMessage(), ['request' => $request]);
    }
    return $this->view($data, Response::HTTP_BAD_REQUEST);
  }

}
