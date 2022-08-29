<?php

namespace App\Controller\Rest;

use App\Entity\GeographicArea;
use App\Utils\FormUtils;
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

/**
 * Class GeographicAreasAPIController
 * @package App\Controller
 * @Route("/geographic-areas")
 */
class GeographicAreasAPIController extends AbstractFOSRestController
{
  const CURRENT_API_VERSION = '1.0';

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
   * List all geographic area
   * @Rest\Get("", name="geographic_areas_api_list")
   *
   * @SWG\Parameter(
   *      name="x-locale",
   *      in="header",
   *      description="Request locale",
   *      required=false,
   *      type="string"
   *  )
   *
   * @SWG\Response(
   *     response=200,
   *     description="Retrieve list of geographic areas",
   *     @SWG\Schema(
   *         type="array",
   *         @SWG\Items(ref=@Model(type=GeographicArea::class, groups={"read"}))
   *     )
   * )
   *
   * @SWG\Tag(name="geographic-areas")
   * @param Request $request
   * @return View
   */
  public function getGeographicAreasAction(Request $request)
  {
    $result = $this->entityManager->getRepository('App\Entity\GeographicArea')->findBy([], ['name' => 'asc']);
    return $this->view($result, Response::HTTP_OK);
  }

  /**
   * Retreive a geographic area by id
   * @Rest\Get("/{id}", name="geographic_area_api_get")
   *
   * @SWG\Parameter(
   *      name="x-locale",
   *      in="header",
   *      description="Request locale",
   *      required=false,
   *      type="string"
   *  )
   *
   * @SWG\Parameter(
   *     name="id",
   *     in="path",
   *     type="string",
   *     format="uuid",
   *     required=true,
   *     description="Geographic area's uuid",
   *     default="5365eab1-8741-43e6-bae1-9326da6734a2"
   * )
   * 
   * @SWG\Response(
   *     response=200,
   *     description="Retreive a GeographicArea",
   *     @Model(type=GeographicArea::class, groups={"read"})
   * )
   *
   * @SWG\Response(
   *     response=404,
   *     description="GeographicArea not found"
   * )
   * @SWG\Tag(name="geographic-areas")
   *
   * @param Request $request
   * @param string $id
   * @return View
   */
  public function getGeographicAreaAction(Request $request, $id)
  {
    try {
      $repository = $this->getDoctrine()->getRepository('App\Entity\GeographicArea');
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
   * Create a geographic area
   * @Rest\Post(name="geographic_areas_api_post")
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
   *      name="x-locale",
   *      in="header",
   *      description="Request locale",
   *      required=false,
   *      type="string"
   *  )
   *
   * @SWG\Parameter(
   *     name="GeographicArea",
   *     in="body",
   *     description="The geographic area to create",
   *     required=true,
   *     @SWG\Schema(
   *         type="object",
   *         ref=@Model(type=GeographicArea::class, groups={"write"})
   *     )
   * )
   *
   * @SWG\Response(
   *     response=201,
   *     description="Create a GeographicArea"
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
   * @SWG\Tag(name="geographic-areas")
   *
   * @param Request $request
   * @return View
   */
  public function postGeographicAreaAction(Request $request)
  {
    $this->denyAccessUnlessGranted(['ROLE_ADMIN' ]);

    $item = new GeographicArea();
    $form = $this->createForm('App\Form\Api\GeographicAreaApiType', $item);
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
   * Edit full geographic area
   * @Rest\Put("/{id}", name="geographic_areas_api_put")
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
   *      name="x-locale",
   *      in="header",
   *      description="Request locale",
   *      required=false,
   *      type="string"
   *  )
   *
   * @SWG\Parameter(
   *     name="id",
   *     in="path",
   *     type="string",
   *     format="uuid",
   *     required=true,
   *     description="Geographic area's uuid",
   *     default="5365eab1-8741-43e6-bae1-9326da6734a2"
   * )
   * 
   * @SWG\Parameter(
   *     name="GeographicArea",
   *     in="body",
   *     description="The recipient to update",
   *     required=true,
   *     @SWG\Schema(
   *         type="object",
   *         ref=@Model(type=GeographicArea::class, groups={"write"})
   *     )
   * )
   *
   * @SWG\Response(
   *     response=200,
   *     description="Edit full GeographicArea"
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
   * @SWG\Tag(name="geographic-areas")
   *
   * @param $id
   * @param Request $request
   * @return View
   */
  public function putGeographicAreaAction($id, Request $request)
  {

    $this->denyAccessUnlessGranted(['ROLE_ADMIN' ]);

    $repository = $this->getDoctrine()->getRepository('App\Entity\GeographicArea');
    $item = $repository->find($id);

    if (!$item) {
      return $this->view(["Object not found"], Response::HTTP_NOT_FOUND);
    }

    $form = $this->createForm('App\Form\Api\GeographicAreaApiType', $item);
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
   * Patch a geographic area
   * @Rest\Patch("/{id}", name="geographic_areas_api_patch")
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
   *      name="x-locale",
   *      in="header",
   *      description="Request locale",
   *      required=false,
   *      type="string"
   *  )
   *
   * @SWG\Parameter(
   *     name="id",
   *     in="path",
   *     type="string",
   *     format="uuid",
   *     required=true,
   *     description="Geographic area's uuid",
   *     default="5365eab1-8741-43e6-bae1-9326da6734a2"
   * )
   * 
   * @SWG\Parameter(
   *     name="GeographicArea",
   *     in="body",
   *     description="The recipient to update",
   *     required=true,
   *     @SWG\Schema(
   *         type="object",
   *         ref=@Model(type=GeographicArea::class, groups={"write"})
   *     )
   * )
   *
   * @SWG\Response(
   *     response=200,
   *     description="Patch a GeographicArea"
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
   * @SWG\Tag(name="geographic-areas")
   *
   * @param $id
   * @param Request $request
   * @return View
   */
  public function patchGeographicAreaAction($id, Request $request)
  {

    $this->denyAccessUnlessGranted(['ROLE_ADMIN' ]);

    $repository = $this->getDoctrine()->getRepository('App\Entity\GeographicArea');
    $item = $repository->find($id);

    if (!$item) {
      return $this->view(["Object not found"], Response::HTTP_NOT_FOUND);
    }

    $form = $this->createForm('App\Form\Api\GeographicAreaApiType', $item);
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
   * Delete a geographic area
   * @Rest\Delete("/{id}", name="geographic_area_api_delete")
   *
   * @SWG\Parameter(
   *     name="id",
   *     in="path",
   *     type="string",
   *     format="uuid",
   *     required=true,
   *     description="Geographic area's uuid",
   *     default="5365eab1-8741-43e6-bae1-9326da6734a2"
   * )
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
   * @SWG\Tag(name="geographic-areas")
   *
   * @Method("DELETE")
   * @param $id
   * @return View
   */
  public function deleteGeographicAreaAction($id)
  {
    $this->denyAccessUnlessGranted(['ROLE_ADMIN' ]);
    $item = $this->getDoctrine()->getRepository('App\Entity\GeographicArea')->find($id);
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
}
