<?php

namespace App\Controller\Rest;

use App\Dto\User;
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
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

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

  private LoggerInterface $logger;

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
   *     name="cf",
   *     in="query",
   *      @OA\Schema(
   *          type="string"
   *      ),
   *     description="Fiscal code of the user"
   * )
   *
   * @OA\Response(
   *     response=200,
   *     description="Retrieve list of users",
   *     @OA\JsonContent(
   *         type="array",
   *         @OA\Items(ref=@Model(type=User::class, groups={"read"}))
   *     )
   * )
   *
   * @OA\Response(
   *     response=403,
   *     description="Access denied"
   * )
   *
   * @OA\Tag(name="users")
   * @param Request $request
   * @return View
   */
  public function getUsersAction(Request $request)
  {
    $this->denyAccessUnlessGranted(['ROLE_OPERATORE', 'ROLE_ADMIN']);

    $result = [];
    $cf = $request->query->get('cf');

    $qb = $this->entityManager->createQueryBuilder()
      ->select('user')
      ->from('App:CPSUser', 'user');

    if (isset($cf)) {
      $qb->andWhere('lower(user.codiceFiscale) = :cf')
        ->setParameter('cf', strtolower($cf));
    }

    $users = $qb
      ->getQuery()
      ->getResult();

    foreach ($users as $u) {
      $result [] = User::fromEntity($u);
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
   *     @Model(type=User::class, groups={"read"})
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
  public function getUserAction(Request $request, $id)
  {
    try {
      $repository = $this->getDoctrine()->getRepository('App\Entity\CPSUser');
      $result = $repository->find($id);
    } catch (\Exception $e) {
      return $this->view(["Object not found"], Response::HTTP_NOT_FOUND);
    }

    if ($result === null) {
      return $this->view(["Object not found"], Response::HTTP_NOT_FOUND);
    }

    $this->denyAccessUnlessGranted(UserVoter::VIEW, $result);

    try {
      return $this->view(User::fromEntity($result), Response::HTTP_OK);
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
  public function postUserAction(Request $request)
  {
    $this->denyAccessUnlessGranted(['ROLE_OPERATORE', 'ROLE_ADMIN']);

    $userDto = new User();
    $form = $this->createForm('App\Form\UserAPIFormType', $userDto);
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
   *             type="object",
   *             ref=@Model(type=User::class, groups={"write"})
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
  public function putUserAction($id, Request $request)
  {
    $repository = $this->getDoctrine()->getRepository('App\Entity\CPSUser');
    $user = $repository->find($id);

    if (!$user) {
      return $this->view(["Object not found"], Response::HTTP_NOT_FOUND);
    }

    $this->denyAccessUnlessGranted(UserVoter::EDIT, $user);

    $userDto = new User();
    $form = $this->createForm('App\Form\UserAPIFormType', $userDto);
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
   *             type="object",
   *             ref=@Model(type=User::class, groups={"write"})
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
    $repository = $this->entityManager->getRepository('App\Entity\CPSUser');
    $user = $repository->find($id);

    if (!$user) {
      return $this->view(["Object not found"], Response::HTTP_NOT_FOUND);
    }

    $this->denyAccessUnlessGranted(UserVoter::EDIT, $user);

    $userDto = User::fromEntity($user);
    $form = $this->createForm('App\Form\UserAPIFormType', $userDto);
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
   * @OA\Tag(name="users")
   *
   * @Method("DELETE")
   * @param $id
   * @return View
   */
  public function deleteAction($id)
  {
    $this->denyAccessUnlessGranted(['ROLE_OPERATORE', 'ROLE_ADMIN']);
    $user = $this->getDoctrine()->getRepository('App\Entity\CPSUser')->find($id);
    if ($user) {
      $this->entityManager->remove($user);
      $this->entityManager->flush();
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
