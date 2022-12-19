<?php

namespace App\Controller\Rest;

use App\Entity\Categoria;
use App\Entity\GeographicArea;
use App\Model\PostalAddress;
use App\Entity\Place;
use App\Utils\FormUtils;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\View\View;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
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
 * @Route("/places")
 */
class PlacesAPIController extends AbstractFOSRestController
{

  /**
   * @var EntityManagerInterface
   */
  private $entityManager;

  /** @var LoggerInterface */
  private $logger;

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
   * List all places
   * @Rest\Get("", name="place_api_list")
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
   * @OA\Response(
   *     response=200,
   *     description="Retrieve list of places",
   *     @OA\JsonContent(
   *         type="array",
   *         @OA\Items(ref=@Model(type=Place::class, groups={"read"}))
   *     )
   * )
   *
   * @OA\Tag(name="places")
   * @param Request $request
   * @return View
   */
  public function getPlacesAction(Request $request): View
  {
    $result = $this->entityManager->getRepository('App\Entity\Place')->findBy([], ['name' => 'asc']);
    return $this->view($result, Response::HTTP_OK);
  }


  /**
   * Retrieve a place by id
   * @Rest\Get("/{id}", name="place_api_get")
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
   * @OA\Response(
   *     response=200,
   *     description="Retreive a Place",
   *     @Model(type=Place::class, groups={"read"})
   * )
   *
   * @OA\Response(
   *     response=404,
   *     description="Place not found"
   * )
   * @OA\Tag(name="places")
   *
   * @param Request $request
   * @param string $id
   * @return View
   */
  public function getPlaceAction(Request $request, $id): View
  {
    try {
      $repository = $this->getDoctrine()->getRepository('App\Entity\Place');
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
   * Create a Place
   * @Rest\Post(name="place_api_post")
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
   *     description="The place to create",
   *     required=true,
   *     @OA\MediaType(
   *         mediaType="application/json",
   *         @OA\Schema(
   *             type="object",
   *             ref=@Model(type=Place::class, groups={"write"})
   *         )
   *     )
   * )
   *
   * @OA\Response(
   *     response=201,
   *     description="Create a Place"
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
   * @OA\Tag(name="places")
   *
   * @param Request $request
   * @return View
   */
  public function postPlaceAction(Request $request): View
  {
    $this->denyAccessUnlessGranted(['ROLE_ADMIN']);

    $place = new Place();
    $form = $this->createForm('App\Form\Api\PlaceApiType', $place);

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
      $this->checkRelations($place, $request);
      $this->entityManager->persist($place);
      $this->entityManager->flush();
    } catch (\Exception $e) {
      return $this->generateExceptionResponse($e, $request);
    }

    return $this->view($place, Response::HTTP_CREATED);
  }




  /**
   * Edit full place
   * @Rest\Put("/{id}", name="place_api_put")
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
   *             ref=@Model(type=Place::class, groups={"write"})
   *         )
   *     )
   * )
   *
   * @OA\Response(
   *     response=200,
   *     description="Edit full Place"
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
   * @OA\Tag(name="places")
   *
   * @param $id
   * @param Request $request
   * @return View
   */
  public function putPlaceAction($id, Request $request): View
  {

    $this->denyAccessUnlessGranted(['ROLE_ADMIN' ]);

    $repository = $this->getDoctrine()->getRepository('App\Entity\Place');
    $place = $repository->find($id);

    if (!$place) {
      return $this->view(["Object not found"], Response::HTTP_NOT_FOUND);
    }
    $form = $this->createForm('App\Form\Api\PlaceApiType', $place);
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
      $this->checkRelations($place, $request);
      $this->entityManager->persist($place);
      $this->entityManager->flush();
    } catch (\Exception $e) {
      return $this->generateExceptionResponse($e, $request);
    }

    return $this->view($place, Response::HTTP_OK);
  }


  /**
   * Patch a place
   * @Rest\Patch("/{id}", name="place_api_patch")
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
   *             ref=@Model(type=Place::class, groups={"write"})
   *         )
   *     )
   * )
   *
   * @OA\Response(
   *     response=200,
   *     description="Patch a Place"
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
   * @OA\Tag(name="places")
   *
   * @param $id
   * @param Request $request
   * @return View
   */
  public function patchPlaceAction($id, Request $request)
  {

    $this->denyAccessUnlessGranted(['ROLE_ADMIN' ]);

    $repository = $this->getDoctrine()->getRepository('App\Entity\Place');
    $place = $repository->find($id);

    if (!$place) {
      return $this->view(["Object not found"], Response::HTTP_NOT_FOUND);
    }

    $form = $this->createForm('App\Form\Api\PlaceApiType', $place);
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
      $this->checkRelations($place, $request);
      $this->entityManager->persist($place);
      $this->entityManager->flush();
    } catch (\Exception $e) {
      return $this->generateExceptionResponse($e, $request);
    }

    return $this->view(["Object Patched Successfully"], Response::HTTP_OK);
  }


  /**
   * Delete a place
   * @Rest\Delete("/{id}", name="place_api_delete")
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
   * @OA\Tag(name="places")
   *
   * @param $id
   * @return View
   */
  public function deletePlaceAction($id): View
  {
    $this->denyAccessUnlessGranted(['ROLE_ADMIN' ]);
    $item = $this->getDoctrine()->getRepository('App\Entity\Place')->find($id);
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
   * @param Place $place
   * @param Request $request
   * @return void
   */
  private function checkRelations(Place $place, Request $request): void
  {

    if ($request->request->has('topic_id') && $request->request->get('topic_id')) {
      $category = $this->entityManager->getRepository('App\Entity\Categoria')->find($request->request->get('topic_id'));
      if (!$category instanceof Categoria) {
        throw new InvalidArgumentException("Category does not exist");
      }
      $place->setTopic($category);
    }
    if ($request->request->has('geographic_area_ids') && $request->request->get('geographic_area_ids')) {
      foreach ($request->request->get('geographic_area_ids') as $geographicAreaId)
      {
        $geographicArea = $this->entityManager->getRepository('App\Entity\GeographicArea')->find($geographicAreaId);
        if (!$geographicArea instanceof GeographicArea) {
          throw new InvalidArgumentException("Geographic area does not exist");
        }
        $place->addGeographicArea($geographicArea);
      }
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
