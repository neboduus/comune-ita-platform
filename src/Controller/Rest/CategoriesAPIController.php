<?php

namespace App\Controller\Rest;

use App\Entity\Categoria;
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
use Symfony\Component\Translation\TranslatorInterface;
use function Aws\boolean_value;

/**
 * Class CategoriesAPIController
 * @package App\Controller
 * @Route("/categories")
 */
class CategoriesAPIController extends AbstractFOSRestController
{
  const CURRENT_API_VERSION = '1.0';

  /**
   * @var EntityManagerInterface
   */
  private $entityManager;

  /** @var LoggerInterface */
  private $logger;

  public function __construct(EntityManagerInterface $entityManager, LoggerInterface $logger)
  {
    $this->entityManager = $entityManager;
    $this->logger = $logger;
  }

  /**
   * List all Categories
   * @Rest\Get("", name="categories_api_list")
   *
   * @SWG\Parameter(
   *      name="not_empty",
   *      in="query",
   *      type="boolean",
   *      required=false,
   *      description="If true empty categories are excluded from results"
   *  )
   *
   * @SWG\Response(
   *     response=200,
   *     description="Retrieve list of categories",
   *     @SWG\Schema(
   *         type="array",
   *         @SWG\Items(ref=@Model(type=Categoria::class, groups={"read"}))
   *     )
   * )
   *
   * @SWG\Tag(name="categories")
   * @param Request $request
   * @return View
   */
  public function getCategoriesAction(Request $request)
  {

    $result = [];
    $notEmpty = boolean_value($request->get('not_empty', false));

    $categories = $this->entityManager->getRepository('App:Categoria')->findBy([], ['name' => 'asc']);
    /** @var Categoria $c */
    foreach ($categories as $c) {
      if ($notEmpty && !$c->hasVisibleRelations()) {
        continue;
      }
      $result []= $c;
    }
    return $this->view($result, Response::HTTP_OK);
  }

  /**
   * Retreive a Category by id
   * @Rest\Get("/{id}", name="category_api_get")
   *
   * @SWG\Response(
   *     response=200,
   *     description="Retreive a Category",
   *     @Model(type=Categoria::class, groups={"read"})
   * )
   *
   * @SWG\Response(
   *     response=404,
   *     description="Category not found"
   * )
   * @SWG\Tag(name="categories")
   *
   * @param Request $request
   * @param string $id
   * @return View
   */
  public function getCategoryAction(Request $request, $id)
  {
    try {
      $repository = $this->getDoctrine()->getRepository('App:Categoria');
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
   * Create a Category
   * @Rest\Post(name="categories_api_post")
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
   *     name="Category",
   *     in="body",
   *     type="json",
   *     description="The category to create",
   *     required=true,
   *     @SWG\Schema(
   *         type="object",
   *         ref=@Model(type=Categoria::class, groups={"write"})
   *     )
   * )
   *
   * @SWG\Response(
   *     response=201,
   *     description="Create a Category"
   * )
   *
   * @SWG\Response(
   *     response=400,
   *     description="Bad request"
   * )
   *
   * @SWG\Response(
   *     response=403,
   *     description="Access denied"
   * )
   *
   * @SWG\Tag(name="categories")
   *
   * @param Request $request
   * @return View
   */
  public function postCategoryAction(Request $request)
  {
    $this->denyAccessUnlessGranted(['ROLE_ADMIN' ]);

    $item = new Categoria();
    $form = $this->createForm('App\Form\Api\CategoryApiType', $item);
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

      $this->entityManager->persist($item);
      $this->entityManager->flush();
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

    return $this->view($item, Response::HTTP_CREATED);
  }

  /**
   * Edit full Category
   * @Rest\Put("/{id}", name="categories_api_put")
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
   *     name="Category",
   *     in="body",
   *     type="json",
   *     description="The category to update",
   *     required=true,
   *     @SWG\Schema(
   *         type="object",
   *         ref=@Model(type=Categoria::class, groups={"write"})
   *     )
   * )
   *
   * @SWG\Response(
   *     response=200,
   *     description="Edit full Category"
   * )
   *
   * @SWG\Response(
   *     response=400,
   *     description="Bad request"
   * )
   *
   * @SWG\Response(
   *     response=403,
   *     description="Access denied"
   * )
   *
   * @SWG\Response(
   *     response=404,
   *     description="Not found"
   * )
   * @SWG\Tag(name="categories")
   *
   * @param $id
   * @param Request $request
   * @return View
   */
  public function putCategoryAction($id, Request $request)
  {

    $this->denyAccessUnlessGranted(['ROLE_ADMIN' ]);

    $repository = $this->getDoctrine()->getRepository('App:Categoria');
    $item = $repository->find($id);

    if (!$item) {
      return $this->view(["Object not found"], Response::HTTP_NOT_FOUND);
    }

    $form = $this->createForm('App\Form\Api\CategoryApiType', $item);
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
      $this->entityManager->persist($item);
      $this->entityManager->flush();
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
   * Patch a Category
   * @Rest\Patch("/{id}", name="categories_api_patch")
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
   *     name="Category",
   *     in="body",
   *     type="json",
   *     description="The category to update",
   *     required=true,
   *     @SWG\Schema(
   *         type="object",
   *         ref=@Model(type=Categoria::class, groups={"write"})
   *     )
   * )
   *
   * @SWG\Response(
   *     response=200,
   *     description="Patch a Category"
   * )
   *
   * @SWG\Response(
   *     response=400,
   *     description="Bad request"
   * )
   *
   * @SWG\Response(
   *     response=403,
   *     description="Access denied"
   * )
   *
   * @SWG\Response(
   *     response=404,
   *     description="Not found"
   * )
   * @SWG\Tag(name="categories")
   *
   * @param $id
   * @param Request $request
   * @return View
   */
  public function patchCategoryAction($id, Request $request)
  {

    $this->denyAccessUnlessGranted(['ROLE_ADMIN' ]);

    $repository = $this->getDoctrine()->getRepository('App:Categoria');
    $item = $repository->find($id);

    if (!$item) {
      return $this->view(["Object not found"], Response::HTTP_NOT_FOUND);
    }

    $form = $this->createForm('App\Form\Api\CategoryApiType', $item);
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
      $this->entityManager->persist($item);
      $this->entityManager->flush();
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
   * Delete a Category
   * @Rest\Delete("/{id}", name="category_api_delete")
   *
   * @SWG\Response(
   *     response=204,
   *     description="The resource was deleted successfully."
   * )
   *
   * @SWG\Response(
   *     response=403,
   *     description="Access denied"
   * )
   *
   * @SWG\Tag(name="categories")
   *
   * @Method("DELETE")
   * @param $id
   * @return View
   */
  public function deleteCategoryAction($id)
  {
    $this->denyAccessUnlessGranted(['ROLE_ADMIN' ]);
    $item = $this->getDoctrine()->getRepository('App:Categoria')->find($id);
    if ($item) {
      $this->entityManager->remove($item);
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
