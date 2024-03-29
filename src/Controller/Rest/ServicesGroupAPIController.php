<?php

namespace App\Controller\Rest;

use App\Dto\ServiceGroupDto;
use App\Entity\Categoria;
use App\Entity\GeographicArea;
use App\Entity\Recipient;
use App\Entity\ServiceGroup;
use App\Model\PublicFile;
use App\Services\FileService\ServiceAttachmentsFileService;
use App\Utils\FormUtils;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use League\Flysystem\FileNotFoundException;
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
use Ramsey\Uuid\Uuid;
use function Aws\boolean_value;

/**
 * Class ServicesAPIController
 * @property EntityManagerInterface em
 * @package App\Controller
 * @Route("/services-groups")
 */
class ServicesGroupAPIController extends AbstractFOSRestController
{

  /** @var EntityManagerInterface  */
  private $em;

  /**
   * @var LoggerInterface
   */
  private $logger;
  /**
   * @var ServiceGroupDto
   */
  private $serviceGroupDto;

  /**
   * @param EntityManagerInterface $em
   * @param LoggerInterface $logger
   * @param ServiceGroupDto $serviceGroupDto
   */
  public function __construct(EntityManagerInterface $em, LoggerInterface $logger, ServiceGroupDto $serviceGroupDto)
  {
    $this->em = $em;
    $this->logger = $logger;
    $this->serviceGroupDto = $serviceGroupDto;
  }


  /**
   * List all Services groups
   * @Rest\Get("", name="services_groups_api_list")
   *
   * @OA\Parameter(
   *      name="topics_id",
   *      in="query",
   *      @OA\Schema(
   *          type="string"
   *      ),
   *      required=false,
   *      description="Id of the category"
   *  )
   *
   *
   * @OA\Parameter(
   *      name="recipient_id",
   *      in="query",
   *      @OA\Schema(
   *          type="string"
   *      ),
   *      required=false,
   *      description="Id of the recipient"
   *  )
   *
   * @OA\Parameter(
   *      name="geographic_area_id",
   *      in="query",
   *      @OA\Schema(
   *          type="string"
   *      ),
   *      required=false,
   *      description="Id of the geographic area"
   *  )
   *
   * @OA\Parameter(
   *      name="not_empty",
   *      in="query",
   *      @OA\Schema(
   *          type="string"
   *      ),
   *      required=false,
   *      description="If true empty services groups are excluded from results"
   *  )
   *
   * @OA\Parameter(
   *      name="order_by",
   *      in="query",
   *      @OA\Schema(
   *          type="string"
   *      ),
   *      required=false,
   *      description="Ordering parameter"
   *  )
   *
   * @OA\Parameter(
   *      name="ascending",
   *      in="query",
   *      @OA\Schema(
   *          type="boolean"
   *      ),
   *      required=false,
   *      description="Ascending or descending"
   *  )
   * 
   * @OA\Parameter(
   *      name="sticky",
   *      in="query",
   *      @OA\Schema(
   *          type="boolean"
   *      ),
   *      required=false,
   *      description="Get sticky or not sticky services"
   *  )
   *
   * @OA\Parameter(
   *      name="limit",
   *      in="query",
   *      @OA\Schema(
   *          type="integer"
   *      ),
   *      required=false,
   *      description="Limit the returned results"
   *  )
   * 
   * @OA\Response(
   *     response=200,
   *     description="Retrieve list of services groups",
   *     @OA\JsonContent(
   *         type="array",
   *         @OA\Items(ref=@Model(type=ServiceGroup::class, groups={"read"}))
   *     )
   * )
   * @OA\Tag(name="services-groups")
   */
  public function getServicesGroupsAction(Request $request)
  {
    $result = [];
    $categoryId = $request->get('topics_id', false);
    $recipientId = $request->get('recipient_id', false);
    $geographicAreaId = $request->get('geographic_area_id', false);
    $notEmpty = boolean_value($request->get('not_empty', false));
    $limit = $request->get('limit', false);
    $criteria = [];

    $criteria['order_by'] = $request->get('order_by', 'name');
    $criteria['ascending'] = boolean_value($request->get('ascending', true));
    $criteria['sticky'] = boolean_value($request->get('sticky'));

    if ($categoryId) {
      $categoriesRepo = $this->em->getRepository('App\Entity\Categoria');
      $category = $categoriesRepo->find($categoryId);
      if (!$category instanceof Categoria) {
        return $this->view(["Category not found"], Response::HTTP_NOT_FOUND);
      }
      $criteria['topics'] = $categoryId;
    }

    if ($recipientId) {
      $recipientsRepo = $this->em->getRepository('App\Entity\Recipient');
      $recipient = $recipientsRepo->find($recipientId);
      if (!$recipient instanceof Recipient) {
        return $this->view(["Recipient not found"], Response::HTTP_NOT_FOUND);
      }
      $criteria['recipients'] = $recipientId;
    }

    if ($geographicAreaId) {
      $geographicAreaRepo = $this->em->getRepository('App\Entity\GeographicArea');
      $geographicArea = $geographicAreaRepo->find($geographicAreaId);
      if (!$geographicArea instanceof GeographicArea) {
        return $this->view(["Geographic area not found"], Response::HTTP_NOT_FOUND);
      }
      $criteria['geographic_areas'] = $geographicAreaId;
    }

    if ($limit) {
      $criteria['limit'] = $limit;
    }

    $services = $this->em->getRepository('App\Entity\ServiceGroup')->findByCriteria($criteria);
    /** @var ServiceGroup $s */
    foreach ($services as $s) {
      if ($notEmpty && $s->getServicesCount() === 0) {
        continue;
      }
      $result []= $this->serviceGroupDto->fromEntity($s);
    }
    try {
      return $this->view($result, Response::HTTP_OK);
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
  }

  /**
   * Retrieve a Service group by id or slug
   * @Rest\Get("/{id}", name="service_group_api_get")
   *
   * @OA\Response(
   *     response=200,
   *     description="Retrieve a Service group by id or slug",
   *     @Model(type=ServiceGroup::class, groups={"read"})
   * )
   *
   * @OA\Response(
   *     response=404,
   *     description="Not found"
   * )
   * @OA\Tag(name="services-groups")
   *
   * @param $id
   * @return View
   */
  public function getServiceAction($id)
  {
    try {
      $repository = $this->getDoctrine()->getRepository('App\Entity\ServiceGroup');

      if (Uuid::isValid($id) ) {
        $result = $repository->find($id);
      } else {
        $result = $repository->findOneBy(['slug' => $id]);
      }

      if ($result === null) {
        return $this->view(["Object not found"], Response::HTTP_NOT_FOUND);
      }

      return $this->view($this->serviceGroupDto->fromEntity($result), Response::HTTP_OK);
    } catch (\Exception $e) {
      return $this->view(["Object not found"], Response::HTTP_NOT_FOUND);
    }
  }


  /**
   * Create a Service Group
   * @Rest\Post(name="services_group_api_post")
   *
   * @Security(name="Bearer")
   *
   * @OA\RequestBody(
   *     description="The service group to create",
   *     required=true,
   *     @OA\MediaType(
   *         mediaType="application/json",
   *         @OA\Schema(
   *             type="object",
   *             ref=@Model(type=ServiceGroup::class, groups={"write"})
   *         )
   *     )
   * )
   *
   * @OA\Response(
   *     response=201,
   *     description="Create a Service group"
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
   * @OA\Tag(name="services-groups")
   *
   * @param Request $request
   * @return View
   */
  public function postServiceAction(Request $request)
  {
    $this->denyAccessUnlessGranted(['ROLE_ADMIN']);

    $serviceGroup = new ServiceGroup();
    $form = $this->createForm('App\Form\Admin\ServiceGroup\ServiceGroupType', $serviceGroup);
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
      $this->em->persist($serviceGroup);
      $this->em->flush();
      return $this->view($this->serviceGroupDto->fromEntity($serviceGroup), Response::HTTP_CREATED);

    } catch (UniqueConstraintViolationException $e) {
      $data = [
        'type' => 'error',
        'title' => 'There was an error during save process',
        'description' => 'Duplicate object'
      ];
      $this->logger->error(
        $e->getMessage(),
        ['request' => $request]
      );
      return $this->view($data, Response::HTTP_INTERNAL_SERVER_ERROR);
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
  }

  /**
   * Edit full Service group
   * @Rest\Put("/{id}", name="service_group_api_put")
   *
   * @Security(name="Bearer")
   *
   * @OA\RequestBody(
   *     description="The service group to create",
   *     required=true,
   *     @OA\MediaType(
   *         mediaType="application/json",
   *         @OA\Schema(
   *             type="object",
   *             ref=@Model(type=ServiceGroup::class, groups={"write"})
   *         )
   *     )
   * )
   *
   * @OA\Response(
   *     response=200,
   *     description="Edit full Service group"
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
   * @OA\Tag(name="services-groups")
   *
   * @param Request $request
   * @return View
   */
  public function putServiceAction($id, Request $request)
  {
    $this->denyAccessUnlessGranted(['ROLE_ADMIN']);

    $repository = $this->getDoctrine()->getRepository('App\Entity\ServiceGroup');
    $serviceGroup = $repository->find($id);

    if (!$serviceGroup) {
      return $this->view(["Object not found"], Response::HTTP_NOT_FOUND);
    }

    try {
      $form = $this->createForm('App\Form\Admin\ServiceGroup\ServiceGroupType', $serviceGroup);
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

      $this->em->persist($serviceGroup);
      $this->em->flush();

      return $this->view(["Object Modified Successfully"], Response::HTTP_OK);
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
  }

  /**
   * Patch a Service group
   * @Rest\Patch("/{id}", name="service_group_api_patch")
   *
   * @Security(name="Bearer")
   *
   * @OA\RequestBody(
   *     description="The service group to create",
   *     required=true,
   *     @OA\MediaType(
   *         mediaType="application/json",
   *         @OA\Schema(
   *             type="object",
   *             ref=@Model(type=ServiceGroup::class)
   *         )
   *     )
   * )
   *
   * @OA\Response(
   *     response=200,
   *     description="Patch a Service group"
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
   * @OA\Tag(name="services-groups")
   *
   * @param Request $request
   * @return View
   */
  public function patchServiceAction($id, Request $request)
  {
    $this->denyAccessUnlessGranted(['ROLE_ADMIN']);

    $em = $this->getDoctrine()->getManager();
    $repository = $this->getDoctrine()->getRepository('App\Entity\ServiceGroup');
    $serviceGroup = $repository->find($id);

    if (!$serviceGroup) {
      return $this->view(["Object not found"], Response::HTTP_NOT_FOUND);
    }

    $form = $this->createForm('App\Form\Admin\ServiceGroup\ServiceGroupType', $serviceGroup);
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
      $em->persist($serviceGroup);
      $em->flush();
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
   * Delete a Service group
   * @Rest\Delete("/{id}", name="service_gropu_api_delete")
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
   * @OA\Tag(name="services-groups")
   *
   * @Method("DELETE")
   */
  public function deleteAction($id)
  {
    $this->denyAccessUnlessGranted(['ROLE_ADMIN']);

    $service = $this->getDoctrine()->getRepository('App\Entity\ServiceGroup')->find($id);
    if ($service) {
      // debated point: should we 404 on an unknown nickname?
      // or should we just return a nice 204 in all cases?
      // we're doing the latter
      $em = $this->getDoctrine()->getManager();
      $em->remove($service);
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
   * Retrieve a ServiceGroup public attachment
   * @Rest\Get("/{id}/attachments/{attachmentType}/{filename}", name="service_group_api_attachment_get")
   *
   * @OA\Response(
   *     response=200,
   *     description="Retrieve attachment file",
   * )
   *
   * @OA\Response(
   *     response=404,
   *     description="Attachment not found"
   * )
   * @OA\Tag(name="services")
   *
   * @param $id
   * @param $attachmentType
   * @param $filename
   * @param ServiceAttachmentsFileService $fileService
   * @return View|Response
   */
  public function attachmentAction($id, $attachmentType, $filename, ServiceAttachmentsFileService $fileService)
  {
    $repository = $this->getDoctrine()->getRepository('App\Entity\ServiceGroup');
    /** @var ServiceGroup $serviceGroup */
    $serviceGroup = $repository->find($id);

    if (!$serviceGroup) {
      return $this->view(["Service group not found"], Response::HTTP_NOT_FOUND);
    }

    if (!in_array($attachmentType, [PublicFile::CONDITIONS_TYPE, PublicFile::COSTS_TYPE])) {
      $this->logger->error("Invalid type $attachmentType");
      return $this->view(["Invalid type: $attachmentType is not supported"], Response::HTTP_BAD_REQUEST);
    }

    if ($attachmentType === PublicFile::CONDITIONS_TYPE) {
      $attachment = $serviceGroup->getConditionAttachmentByName($filename);
    } elseif ($attachmentType === PublicFile::COSTS_TYPE) {
      $attachment = $serviceGroup->getCostAttachmentByName($filename);
    } else {
      $attachment = null;
    }

    if (!$attachment) {
      return $this->view(["$filename not found"], Response::HTTP_NOT_FOUND);
    }

    try {
      return $fileService->download($attachment->getOriginalName(), $serviceGroup, $attachmentType);
    } catch (FileNotFoundException $e) {
      $this->logger->error("Attachment $filename not found for type $attachmentType");
      return $this->view(["$filename not found"], Response::HTTP_NOT_FOUND);
    }
  }
}
