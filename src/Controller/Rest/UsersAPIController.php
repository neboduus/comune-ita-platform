<?php

namespace App\Controller\Rest;

use App\Dto\User;
use App\Services\InstanceService;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use Psr\Log\LoggerInterface;
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
 * Class UsersAPIController
 * @property EntityManagerInterface em
 * @property InstanceService is
 * @package App\Controller
 * @Route("/users")
 */
class UsersAPIController extends AbstractFOSRestController
{
  const CURRENT_API_VERSION = '1.0';

  private $em;
  private $is;
  /**
   * @var TranslatorInterface $translator
   */
  private $translator;

  /** @var LoggerInterface */
  private $logger;

  public function __construct(TranslatorInterface $translator, EntityManagerInterface $em, InstanceService $is, LoggerInterface $logger)
  {
    $this->translator = $translator;
    $this->em = $em;
    $this->is = $is;
    $this->logger = $logger;
  }

  /**
   * List all Users
   * @Rest\Get("", name="users_api_list")
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
   *     name="cf",
   *     in="query",
   *     type="string",
   *     description="Fiscal code of the user"
   * )
   *
   * @SWG\Response(
   *     response=200,
   *     description="Retrieve list of users",
   *     @SWG\Schema(
   *         type="array",
   *         @SWG\Items(ref=@Model(type=User::class))
   *     )
   * )
   * @SWG\Tag(name="users")
   * @param Request $request
   * @return View
   */
  public function getUsersAction(Request $request)
  {
    $result = [];
    $cf = $request->query->get('cf');

    $qb = $this->em->createQueryBuilder()
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
      $result []= User::fromEntity($u);
    }
    return $this->view($result, Response::HTTP_OK);
  }

  /**
   * Retreive a User by id
   * @Rest\Get("/{id}", name="user_api_get")
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
   *     response=200,
   *     description="Retreive a User",
   *     @Model(type=User::class)
   * )
   *
   * @SWG\Response(
   *     response=404,
   *     description="User not found"
   * )
   * @SWG\Tag(name="users")
   *
   * @param Request $request
   * @param string $id
   * @return View
   */
  public function getUserAction(Request $request, $id)
  {
    try {
      $repository = $this->getDoctrine()->getRepository('App:CPSUser');
      $result = $repository->find($id);

      if ($result === null) {
        return $this->view("Object not found", Response::HTTP_NOT_FOUND);
      }

      return $this->view(User::fromEntity($result), Response::HTTP_OK);
    } catch (\Exception $e) {
      return $this->view("Object not found", Response::HTTP_NOT_FOUND);
    }
  }

  /**
   * Create a User
   * @Rest\Post(name="users_api_post")
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
   *     name="User",
   *     in="body",
   *     type="json",
   *     description="The user to create",
   *     required=true,
   *     @SWG\Schema(
   *         type="object",
   *         ref=@Model(type=User::class)
   *     )
   * )
   *
   * @SWG\Response(
   *     response=201,
   *     description="Create a User"
   * )
   *
   * @SWG\Response(
   *     response=400,
   *     description="Bad request"
   * )
   * @SWG\Tag(name="users")
   *
   * @param Request $request
   * @return View
   */
  public function postUserAction(Request $request)
  {
    $userDto = new User();
    $form = $this->createForm('App\Form\UserAPIFormType', $userDto);
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

    $em = $this->getDoctrine()->getManager();

    $user = $userDto->toEntity();

    try {
      $user->addRole('ROLE_USER')
        ->addRole('ROLE_CPS_USER')
        ->setEnabled(true)
        ->setPassword('');

      $em->persist($user);
      $em->flush();
    } catch (\Exception $e) {

      $data = [
        'type' => 'error',
        'title' => 'There was an error during save process',
        'description' => $e->getMessage()
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
   * @SWG\Parameter(
   *     name="Authorization",
   *     in="header",
   *     description="The authentication Bearer",
   *     required=true,
   *     type="string"
   * )
   *
   * @SWG\Parameter(
   *     name="User",
   *     in="body",
   *     type="json",
   *     description="The user to create",
   *     required=true,
   *     @SWG\Schema(
   *         type="object",
   *         ref=@Model(type=User::class)
   *     )
   * )
   *
   * @SWG\Response(
   *     response=200,
   *     description="Edit full User"
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
   * @SWG\Tag(name="users")
   *
   * @param $id
   * @param Request $request
   * @return View
   */
  public function putUserAction($id, Request $request)
  {
    $repository = $this->getDoctrine()->getRepository('App:CPSUser');
    $user = $repository->find($id);

    if (!$user) {
      return $this->view("Object not found", Response::HTTP_NOT_FOUND);
    }
    $userDto = new User();
    $form = $this->createForm('App\Form\UserAPIFormType', $userDto);
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

    $em = $this->getDoctrine()->getManager();
    $user = $userDto->toEntity($user);

    try {
      $em->persist($user);
      $em->flush();
    } catch (\Exception $e) {

      $data = [
        'type' => 'error',
        'title' => $e->getMessage()
      ];
      $this->logger->error(
        $e->getMessage(),
        ['request' => $request]
      );
      return $this->view($data, Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    return $this->view("Object Modified Successfully", Response::HTTP_OK);
  }

  /**
   * Patch a User
   * @Rest\Patch("/{id}", name="users_api_patch")
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
   *     name="User",
   *     in="body",
   *     type="json",
   *     description="The service to create",
   *     required=true,
   *     @SWG\Schema(
   *         type="object",
   *         ref=@Model(type=User::class)
   *     )
   * )
   *
   * @SWG\Response(
   *     response=200,
   *     description="Patch a User"
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
   * @SWG\Tag(name="users")
   *
   * @param $id
   * @param Request $request
   * @return View
   */
  public function patchuserAction($id, Request $request)
  {
    $em = $this->getDoctrine()->getManager();
    $repository = $this->getDoctrine()->getRepository('App:CPSUser');
    $user = $repository->find($id);

    if (!$user) {
      return $this->view("Object not found", Response::HTTP_NOT_FOUND);
    }
    $userDto = User::fromEntity($user);
    $form = $this->createForm('App\Form\UserAPIFormType', $userDto);
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

    $user = $userDto->toEntity($user);

    try {
      $em->persist($user);
      $em->flush();
    } catch (\Exception $e) {

      $data = [
        'type' => 'error',
        'title' => 'There was an error during save process'
      ];
      $this->logger->error(
        $e->getMessage(),
        ['request' => $request]
      );
      return $this->view($data, Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    return $this->view("Object Patched Successfully", Response::HTTP_OK);
  }

  /**
   * Delete a User
   * @Rest\Delete("/{id}", name="users_api_delete")
   *
   * @SWG\Response(
   *     response=204,
   *     description="The resource was deleted successfully."
   * )
   * @SWG\Tag(name="users")
   *
   * @Method("DELETE")
   * @param $id
   * @return View
   */
  public function deleteAction($id)
  {
    $user = $this->getDoctrine()->getRepository('App:CPSUser')->find($id);
    if ($user) {
      // debated point: should we 404 on an unknown nickname?
      // or should we just return a nice 204 in all cases?
      // we're doing the latter
      $em = $this->getDoctrine()->getManager();
      $em->remove($user);
      $em->flush();
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
