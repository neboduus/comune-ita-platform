<?php

namespace App\Controller\Rest;

use App\Dto\User;
use App\Dto\Operator;
use App\Dto\Admin;
use App\Entity\CPSUser;
use App\Entity\OperatoreUser;
use App\Security\Voters\UserVoter;
use App\Services\InstanceService;
use App\Utils\FormUtils;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
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

/**
 * Class UsersAPIController
 * @property EntityManagerInterface em
 * @property InstanceService is
 * @package App\Controller
 * @Route("/users")
 */
class UsersAPIController extends AbstractFOSRestController
{
  const CURRENT_API_VERSION = '1.0';

  /** @var LoggerInterface */
  private LoggerInterface $logger;

  /** @var EntityManagerInterface */
  private EntityManagerInterface $entityManager;

  /**
   * @param EntityManagerInterface $entityManager
   * @param LoggerInterface $logger
   */
  public function __construct(EntityManagerInterface $entityManager, LoggerInterface $logger)
  {
    $this->logger = $logger;
    $this->entityManager = $entityManager;
  }

  /**
   * List all Users
   * @Rest\Get("", name="users_api_list")
   *
   * @Security(name="Bearer")
   *
   * @OA\Parameter(
   *     name="role",
   *     description="Comma separated user's roles. Available roles are user, operator and admin",
   *     in="query",
   *      @OA\Schema(
   *          type="string",
   *          default="user"
   *      )
   * )
   *
   * @OA\Parameter(
   *     name="cf",
   *     in="query",
   *      @OA\Schema(
   *          type="string",
   *          example="XXXXXX00XZ00X000X"
   *      ),
   *     description="Fiscal code of the user. This filter is only available if the user role is selected"
   * )
   *
   * @OA\Parameter(
   *     name="username",
   *     in="query",
   *      @OA\Schema(
   *          type="string"
   *      ),
   *     description="User's username. This filter is only available if the operator role or the admin role is selected"
   * )
   *
   * @OA\Parameter(
   *     name="user_group_id",
   *     in="query",
   *      @OA\Schema(
   *          type="string",
   *          format="uuid"
   *      ),
   *     description="Operator's user group. This filter is only available if the operator role is selected"
   * )
   *
   * @OA\Response(
   *     response=200,
   *     description="Retrieve list of users",
   *     @OA\JsonContent(
   *         type="array",
   *         @OA\Items(
   *          oneOf={
   *              @OA\Schema(ref=@Model(type=User::class, groups={"read"})),
   *              @OA\Schema(ref=@Model(type=Operator::class, groups={"read"})),
   *              @OA\Schema(ref=@Model(type=Admin::class, groups={ "read"}))
   *            }
   *        )
   *     )
   * )
   *
   * @OA\Response(
   *     response=403,
   *     description="Access denied"
   * )
   *
   * @OA\Response(
   *     response=400,
   *     description="Bad request"
   * )
   *
   * @OA\Tag(name="users")
   * @param Request $request
   * @return View
   */
  public function getUsersAction(Request $request)
  {
    $user = $this->getUser();

    $roles = $request->query->get('roles', User::USER_TYPE_CPS);
    $roles = explode(',', $roles);

    $fiscalCodeParameter = $request->query->get('cf', false);
    $userGroupId = $request->query->get('user_group_id', false);
    $username = $request->query->get('username', false);

    // Validate parameters with roles
    if ($fiscalCodeParameter and !in_array(User::USER_TYPE_CPS, $roles)) {
      $data = [
        'type' => 'error',
        'title' => 'Invalid cf query parameter',
        'description' => 'You cannot search for a user by cf if you do not select the user role ',
      ];
      return $this->view($data, Response::HTTP_BAD_REQUEST);
    }

    if ($userGroupId && !in_array(Operator::USER_TYPE_OPERATORE, $roles)) {
      $data = [
        'type' => 'error',
        'title' => 'Invalid user_group_id query parameter',
        'description' => 'You cannot search for a user by user_group_id if you do not select the operator role ',
      ];
      return $this->view($data, Response::HTTP_BAD_REQUEST);
    }

    if ($username && !in_array([Operator::USER_TYPE_OPERATORE, Admin::USER_TYPE_ADMIN], $roles)) {
      $data = [
        'type' => 'error',
        'title' => 'Invalid username query parameter',
        'description' => 'You cannot search for a user by username if you do not select the operator or the admin role ',
      ];
      return $this->view($data, Response::HTTP_BAD_REQUEST);
    }

    $result = [];

    // USERS
    if (in_array(User::USER_TYPE_CPS, $roles)) {
      $qb = $this->entityManager->createQueryBuilder()
        ->select('user')
        ->from('App:CPSUser', 'user');

      // If requester user is a user return only itself
      if ($user instanceof CPSUser) {
        $qb->andWhere('user.username = :username')
          ->setParameter('username', $user->getUsername());
      }

      if ($fiscalCodeParameter) {
        $qb->andWhere('lower(user.codiceFiscale) = :cf')
          ->setParameter('cf', strtolower($fiscalCodeParameter));
      }

      $users = $qb->getQuery()->getResult();
      foreach ($users as $u) {
        $result[] = User::fromEntity($u);
      }
    }

    // OPERATORS
    if (in_array(Operator::USER_TYPE_OPERATORE, $roles)) {
      // Only operators or admins can query operators
      $this->denyAccessUnlessGranted(['ROLE_OPERATORE', 'ROLE_ADMIN']);

      $qb = $this->entityManager->createQueryBuilder()
        ->select('operator')
        ->from('App:OperatoreUser', 'operator');

      if ($username) {
        $qb->andWhere('operator.username = :username')
          ->setParameter('username', $username);
      }

      if ($userGroupId) {
        $qb->andWhere(':userGroupId MEMBER OF operator.userGroups')
          ->setParameter('userGroupId', $userGroupId);
      }

      $operators = $qb->getQuery()->getResult();
      foreach ($operators as $o) {
        $result[] = Operator::fromEntity($o);
      }
    }

    // ADMINS
    if (in_array(Admin::USER_TYPE_ADMIN, $roles)) {
      // Only operators or admins can query admins
      $this->denyAccessUnlessGranted(['ROLE_OPERATORE', 'ROLE_ADMIN']);

      $qb = $this->entityManager->createQueryBuilder()
        ->select('admin')
        ->from('App:AdminUser', 'admin');

      if ($username) {
        $qb->andWhere('admin.username = :username')
          ->setParameter('username', $username);
      }

      $admins = $qb->getQuery()->getResult();
      foreach ($admins as $a) {
        $result[] = Admin::fromEntity($a);
      }
    }

    return $this->view($result, Response::HTTP_OK);
  }

  /**
   * Retrieve a User by id
   * @Rest\Get("/{id}", name="user_api_get")
   *
   * @Security(name="Bearer")
   *
   * @OA\Response(
   *     response=200,
   *     description="Retrieve a User",
   *     @OA\JsonContent(
   *       oneOf={
   *          @OA\Schema(ref=@Model(type=User::class, groups={"read"})),
   *          @OA\Schema(ref=@Model(type=Operator::class, groups={"read"})),
   *          @OA\Schema(ref=@Model(type=Admin::class, groups={ "read"}))
   *       }
   *    )
   * )
   *
   * @OA\Response(
   *     response=403,
   *     description="Access denied"
   * )
   *
   * @OA\Response(
   *     response=404,
   *     description="User not found"
   * )
   * @OA\Tag(name="users")
   *
   * @param Request $request
   * @param string $id
   * @return View
   */
  public function getUserAction(Request $request, string $id): View
  {
    try {
      $repository = $this->getDoctrine()->getRepository('App\Entity\User');
      $result = $repository->find($id);
    } catch (\Exception $e) {
      return $this->view(["Object not found"], Response::HTTP_NOT_FOUND);
    }

    if (!$result) {
      return $this->view(["Object not found"], Response::HTTP_NOT_FOUND);
    }

    $this->denyAccessUnlessGranted(UserVoter::VIEW, $result);

    try {
      if ($result instanceof CPSUser) {
        $user = User::fromEntity($result);
      } elseif ($result instanceof OperatoreUser) {
        $user = Operator::fromEntity($result);
      } else {
        $user = Admin::fromEntity($result);
      }

      return $this->view($user, Response::HTTP_OK);
    } catch (\Exception $e) {
      $data = [
        'type' => 'error',
        'title' => 'There was an error',
        'description' => $e->getMessage(),
      ];

      return $this->view($data, Response::HTTP_INTERNAL_SERVER_ERROR);
    }
  }

  /**
   * Create a User
   * @Rest\Post(name="users_api_post")
   *
   * @Security(name="Bearer")
   *
   * @OA\RequestBody(
   *     description="The user to create",
   *     required=true,
   *     @OA\MediaType(
   *         mediaType="application/json",
   *         @OA\Schema(
   *             type="object",
   *             ref=@Model(type=User::class, groups={"write"})
   *         )
   *     )
   * )
   *
   * @OA\Response(
   *     response=201,
   *     description="Create a User"
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
   * @OA\Tag(name="users")
   *
   * @param Request $request
   * @return View
   */
  public function postUserAction(Request $request): View
  {
    $this->denyAccessUnlessGranted(['ROLE_OPERATORE', 'ROLE_ADMIN']);

    $userDto = new User();
    $form = $this->createForm('UserApiType', $userDto);
    try {
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

      $user = $userDto->toEntity();
      $user->addRole('ROLE_USER')
        ->addRole('ROLE_CPS_USER')
        ->setEnabled(true)
        ->setPassword('');

      $this->entityManager->persist($user);
      $this->entityManager->flush();

    } catch (UniqueConstraintViolationException $e) {
      $data = [
        'type' => 'error',
        'title' => 'Duplicate user',
        'description' => 'An user with this passed fiscal code is already present',
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
        'description' => 'Contact technical support at support@opencontent.it',
      ];
      $this->logger->error(
        $e->getMessage(),
        ['request' => $request]
      );

      return $this->view($data, Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    return $this->view(User::fromEntity($user), Response::HTTP_CREATED);
  }

  /**
   * Edit full User
   * @Rest\Put("/{id}", name="users_api_put")
   *
   * @Security(name="Bearer")
   *
   * @OA\RequestBody(
   *     description="The user to create",
   *     required=true,
   *     @OA\MediaType(
   *         mediaType="application/json",
   *         @OA\Schema(
   *             oneOf={
   *                @OA\Schema(ref=@Model(type=User::class, groups={"write"})),
   *                @OA\Schema(ref=@Model(type=Operator::class, groups={"write"})),
   *                @OA\Schema(ref=@Model(type=Admin::class, groups={ "write"}))
   *            }
   *         )
   *     )
   * )
   *
   * @OA\Response(
   *     response=200,
   *     description="Edit full User"
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
   * @OA\Tag(name="users")
   *
   * @param $id
   * @param Request $request
   * @return View
   */
  public function putUserAction($id, Request $request): View
  {
    $repository = $this->getDoctrine()->getRepository('App\Entity\User');
    $user = $repository->find($id);

    if (!$user) {
      return $this->view(["Object not found"], Response::HTTP_NOT_FOUND);
    }

    $this->denyAccessUnlessGranted(UserVoter::EDIT, $user);

    if ($user instanceof CPSUser) {
      $userDto = new User();
      $form = $this->createForm('UserApiType', $userDto);
    } elseif ($user instanceof OperatoreUser) {
      $userDto = new Operator();
      $form = $this->createForm('OperatorApiType', $userDto);
    } else {
      $userDto = new Admin();
      $form = $this->createForm('App\Form\AdminApiType', $userDto);
    }

    $this->processForm($request, $form);

    if ($form->isSubmitted() && !$form->isValid()) {
      $errors = FormUtils::getErrorsFromForm($form);
      $data = [
        'type' => 'put_validation_error',
        'title' => 'There was a validation error',
        'errors' => $errors,
      ];

      return $this->view($data, Response::HTTP_BAD_REQUEST);
    }

    $user = $userDto->toEntity($user);

    try {
      $this->entityManager->persist($user);
      $this->entityManager->flush();
    } catch (\Exception $e) {

      $data = [
        'type' => 'error',
        'title' => 'There was an error during save process',
        'description' => 'Contact technical support at support@opencontent.it',
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
   * Patch a User
   * @Rest\Patch("/{id}", name="users_api_patch")
   *
   * @Security(name="Bearer")
   *
   * @OA\RequestBody(
   *     description="The service to create",
   *     required=true,
   *     @OA\MediaType(
   *         mediaType="application/json",
   *         @OA\Schema(
   *             oneOf={
   *                @OA\Schema(ref=@Model(type=User::class, groups={"write"})),
   *                @OA\Schema(ref=@Model(type=Operator::class, groups={"write"})),
   *                @OA\Schema(ref=@Model(type=Admin::class, groups={ "write"}))
   *            }
   *         )
   *     )
   * )
   *
   * @OA\Response(
   *     response=200,
   *     description="Patch a User"
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
   * @OA\Tag(name="users")
   *
   * @param $id
   * @param Request $request
   * @return View
   */
  public function patchuserAction($id, Request $request)
  {
    $repository = $this->entityManager->getRepository('App\Entity\User');
    $user = $repository->find($id);

    if (!$user) {
      return $this->view(["Object not found"], Response::HTTP_NOT_FOUND);
    }

    $this->denyAccessUnlessGranted(UserVoter::EDIT, $user);

    if ($user instanceof CPSUser) {
      $userDto = new User();
      $form = $this->createForm('UserApiType', $userDto);
    } elseif ($user instanceof OperatoreUser) {
      $userDto = new Operator();
      $form = $this->createForm('OperatorApiType', $userDto);
    } else {
      $userDto = new Admin();
      $form = $this->createForm('App\Form\AdminApiType', $userDto);
    }

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

    $user = $userDto->toEntity($user);

    try {
      $this->entityManager->persist($user);
      $this->entityManager->flush();
    } catch (\Exception $e) {

      $data = [
        'type' => 'error',
        'title' => 'There was an error during save process',
        'description' => 'Contact technical support at support@opencontent.it',
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
   * Delete a User
   * @Rest\Delete("/{id}", name="users_api_delete")
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
   * @OA\Response(
   *     response=404,
   *     description="Not found"
   * )
   *
   * @OA\Tag(name="users")
   *
   * @Method("DELETE")
   * @param $id
   * @return View
   */
  public function deleteAction($id)
  {
    $user = $this->getDoctrine()->getRepository('App\Entity\User')->find($id);

    if (!$user) {
      return $this->view(["Object not found"], Response::HTTP_NOT_FOUND);
    }

    $this->denyAccessUnlessGranted(UserVoter::DELETE, $user);
    $this->entityManager->remove($user);
    $this->entityManager->flush();

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
