<?php

namespace App\Controller\Rest;

use App\Entity\Recipient;
use App\Utils\FormUtils;
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
 * Class RecipientsAPIController
 * @package App\Controller
 * @Route("/recipients")
 */
class RecipientsAPIController extends AbstractFOSRestController
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
   * List all Recipients
   * @Rest\Get("", name="recipients_api_list")
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
   *     description="Retrieve list of recipients",
   *     @OA\JsonContent(
   *         type="array",
   *         @OA\Items(ref=@Model(type=Recipient::class, groups={"read"}))
   *     )
   * )
   *
   * @OA\Tag(name="recipients")
   * @param Request $request
   * @return View
   */
  public function getRecipientsAction(Request $request)
  {
    $result = $this->entityManager->getRepository('App\Entity\Recipient')->findBy([], ['name' => 'asc']);
    return $this->view($result, Response::HTTP_OK);
  }

  /**
   * Retrieve a Recipient by id
   * @Rest\Get("/{id}", name="recipient_api_get")
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
   *     description="Retrieve a Recipient",
   *     @Model(type=Recipient::class, groups={"read"})
   * )
   *
   * @OA\Response(
   *     response=404,
   *     description="Recipient not found"
   * )
   * @OA\Tag(name="recipients")
   *
   * @param Request $request
   * @param string $id
   * @return View
   */
  public function getRecipientAction(Request $request, $id)
  {
    try {
      $repository = $this->getDoctrine()->getRepository('App\Entity\Recipient');
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
   * Create a Recipient
   * @Rest\Post(name="recipients_api_post")
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
   *     description="The recipient to create",
   *     required=true,
   *     @OA\MediaType(
   *         mediaType="application/json",
   *         @OA\Schema(
   *             type="object",
   *             ref=@Model(type=Recipient::class, groups={"write"})
   *         )
   *     )
   * )
   *
   * @OA\Response(
   *     response=201,
   *     description="Create a Recipient"
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
   * @OA\Tag(name="recipients")
   *
   * @param Request $request
   * @return View
   */
  public function postRecipientAction(Request $request)
  {
    $this->denyAccessUnlessGranted(['ROLE_ADMIN' ]);

    $item = new Recipient();
    $form = $this->createForm('App\Form\Api\RecipientApiType', $item);
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
   * Edit full Recipient
   * @Rest\Put("/{id}", name="recipients_api_put")
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
   *             ref=@Model(type=Recipient::class, groups={"write"})
   *         )
   *     )
   * )
   *
   * @OA\Response(
   *     response=200,
   *     description="Edit full Recipient"
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
   * @OA\Tag(name="recipients")
   *
   * @param $id
   * @param Request $request
   * @return View
   */
  public function putRecipientAction($id, Request $request)
  {

    $this->denyAccessUnlessGranted(['ROLE_ADMIN' ]);

    $repository = $this->getDoctrine()->getRepository('App\Entity\Recipient');
    $item = $repository->find($id);

    if (!$item) {
      return $this->view(["Object not found"], Response::HTTP_NOT_FOUND);
    }

    $form = $this->createForm('App\Form\Api\RecipientApiType', $item);
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
   * Patch a Recipient
   * @Rest\Patch("/{id}", name="recipients_api_patch")
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
   *             ref=@Model(type=Recipient::class, groups={"write"})
   *         )
   *     )
   * )
   *
   * @OA\Response(
   *     response=200,
   *     description="Patch a Recipient"
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
   * @OA\Tag(name="recipients")
   *
   * @param $id
   * @param Request $request
   * @return View
   */
  public function patchRecipientAction($id, Request $request)
  {

    $this->denyAccessUnlessGranted(['ROLE_ADMIN' ]);

    $repository = $this->getDoctrine()->getRepository('App\Entity\Recipient');
    $item = $repository->find($id);

    if (!$item) {
      return $this->view(["Object not found"], Response::HTTP_NOT_FOUND);
    }

    $form = $this->createForm('App\Form\Api\RecipientApiType', $item);
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
   * Delete a Recipient
   * @Rest\Delete("/{id}", name="recipient_api_delete")
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
   * @OA\Tag(name="recipients")
   *
   * @Method("DELETE")
   * @param $id
   * @return View
   */
  public function deleteRecipientAction($id)
  {
    $this->denyAccessUnlessGranted(['ROLE_ADMIN' ]);
    $item = $this->getDoctrine()->getRepository('App\Entity\Recipient')->find($id);
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
