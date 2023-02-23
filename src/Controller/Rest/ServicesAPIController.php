<?php

namespace App\Controller\Rest;

use App\Dto\ServiceDto;
use App\Entity\Erogatore;
use App\Entity\Servizio;
use App\Model\PublicFile;
use App\Model\Service;
use App\Protocollo\ProtocolloHandlerRegistry;
use App\Services\FileService\ServiceAttachmentsFileService;
use App\Services\FormServerApiAdapterService;
use App\Services\InstanceService;
use App\Services\Manager\ServiceManager;
use App\Utils\FormUtils;
use Ramsey\Uuid\Uuid;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use League\Flysystem\FileNotFoundException;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use OpenApi\Annotations as OA;
use Psr\Log\LoggerInterface;
use ReflectionException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class ServicesAPIController
 * @property EntityManagerInterface em
 * @property InstanceService is
 * @package App\Controller
 * @Route("/services")
 */
class ServicesAPIController extends AbstractFOSRestController
{
  const CURRENT_API_VERSION = '1.0';

  private EntityManagerInterface $em;

  private InstanceService $is;

  private LoggerInterface $logger;

  private FormServerApiAdapterService $formServerApiAdapterService;

  private ProtocolloHandlerRegistry $handlerRegistry;

  private array $handlerList = [];

  private ServiceManager $serviceManager;

  private ServiceDto $serviceDto;


  /**
   * ServicesAPIController constructor.
   * @param EntityManagerInterface $em
   * @param InstanceService $is
   * @param LoggerInterface $logger
   * @param FormServerApiAdapterService $formServerApiAdapterService
   * @param ProtocolloHandlerRegistry $handlerRegistry
   * @param ServiceManager $serviceManager
   * @param ServiceDto $serviceDto
   */
  public function __construct(
    EntityManagerInterface      $em,
    InstanceService             $is,
    LoggerInterface             $logger,
    FormServerApiAdapterService $formServerApiAdapterService,
    ProtocolloHandlerRegistry   $handlerRegistry,
    ServiceManager              $serviceManager,
    ServiceDto                  $serviceDto
  )
  {
    $this->em = $em;
    $this->is = $is;
    $this->logger = $logger;
    $this->formServerApiAdapterService = $formServerApiAdapterService;
    $this->handlerRegistry = $handlerRegistry;
    $this->serviceManager = $serviceManager;
    $this->serviceDto = $serviceDto;

    foreach ($this->handlerRegistry->getAvailableHandlers() as $alias => $handler) {
      $this->handlerList[] = $alias;
    }
  }


  /**
   * List all Services
   * @Rest\Get("", name="services_api_list")
   *
   * @OA\Parameter(
   *      name="q",
   *      in="query",
   *      @OA\Schema(type="string"),
   *      required=false,
   *      description="Search text"
   *  )
   *
   * @OA\Parameter(
   *      name="status",
   *      in="query",
   *      @OA\Schema(type="string"),
   *      required=false,
   *      description="Status of service, accepted values: 1 - Pubblished, 2 - Suspended, 4 - scheduled"
   *  )
   *
   * @OA\Parameter(
   *      name="identifier",
   *      in="query",
   *      @OA\Schema(type="string"),
   *      required=false,
   *      description="Public service identifier"
   *  )
   *
   * @OA\Parameter(
   *      name="topics_id",
   *      in="query",
   *      @OA\Schema(type="string"),
   *      required=false,
   *      description="Id of the category"
   *  )
   *
   * @OA\Parameter(
   *      name="service_group_id",
   *      in="query",
   *      @OA\Schema(type="string"),
   *      required=false,
   *      description="Id of the service group"
   *  )
   *
   * @OA\Parameter(
   *      name="recipient_id",
   *      in="query",
   *      @OA\Schema(type="string"),
   *      required=false,
   *      description="Id of the recipient"
   *  )
   *
   * @OA\Parameter(
   *      name="geographic_area_id",
   *      in="query",
   *      @OA\Schema(type="string"),
   *      required=false,
   *      description="Id of the geographic area"
   *  )
   *
   * @OA\Parameter(
   *      name="user_group_ids",
   *      in="query",
   *      @OA\Schema(type="string"),
   *      required=false,
   *      description="Comma separated user group ids"
   *  )
   *
   * @OA\Parameter(
   *      name="grouped",
   *      in="query",
   *      @OA\Schema(type="string"),
   *      required=false,
   *      description="If false grouped services are excluded from results"
   *  )
   *
   * @OA\Parameter(
   *      name="order_by",
   *      in="query",
   *      @OA\Schema(type="string"),
   *      required=false,
   *      description="Ordering parameter"
   *  )
   *
   * @OA\Parameter(
   *      name="ascending",
   *      in="query",
   *      @OA\Schema(type="boolean"),
   *      required=false,
   *      description="Ascending or descending"
   *  )
   *
   * @OA\Parameter(
   *      name="sticky",
   *      in="query",
   *      @OA\Schema(type="boolean"),
   *      required=false,
   *      description="Get sticky or not sticky services"
   *  )
   *
   * @OA\Parameter(
   *      name="limit",
   *      in="query",
   *      @OA\Schema(type="integer"),
   *      required=false,
   *      description="Limit the returned results"
   *  )
   *
   * @OA\Response(
   *     response=200,
   *     description="Retrieve list of services",
   *     @OA\JsonContent(
   *         type="array",
   *         @OA\Items(ref=@Model(type=Service::class, groups={"read"}))
   *     )
   * )
   * @OA\Tag(name="services")
   */
  public function getServicesAction(Request $request): View
  {

    try {
      $result = [];
      $services = $this->serviceManager->getServices($request);

      foreach ($services as $s) {
        $result [] = $this->serviceDto->fromEntity($s, $this->formServerApiAdapterService->getFormServerPublicUrl());
      }

      return $this->view($result, Response::HTTP_OK);
    } catch (NotFoundHttpException $e) {
      return $this->view([$e->getMessage()], Response::HTTP_NOT_FOUND);
    } catch (\Exception $e) {
      $data = [
        'type' => 'error',
        'title' => 'There was an error during fetch process',
        'description' => $e->getMessage(),
      ];
      $this->logger->error(
        $e->getMessage(),
        ['request' => $request]
      );

      return $this->view($data, Response::HTTP_INTERNAL_SERVER_ERROR);
    }
  }

  /**
   * Retrieve service's facets
   * @Rest\Get("/facets", name="service_api_facets")
   *
   * @OA\Response(
   *     response=200,
   *     description="Retrieve service's facets"
   * )
   *
   * @OA\Tag(name="services")
   *
   */
  public function facetsAction(): View
  {
    $data = $this->serviceManager->getFacets();
    return $this->view($data, Response::HTTP_OK);
  }

  /**
   * Retrieve a Service
   * @Rest\Get("/{id}", name="service_api_get")
   *
   * @OA\Response(
   *     response=200,
   *     description="Retrieve a service by uuid or identifier",
   *     @Model(type=Service::class, groups={"read"})
   * )
   *
   * @OA\Response(
   *     response=404,
   *     description="Service not found"
   * )
   * @OA\Tag(name="services")
   *
   * @param $id
   * @param Request $request
   * @return View
   */
  public function getServiceAction($id, Request $request): View
  {
    try {
      $repository = $this->getDoctrine()->getRepository('App\Entity\Servizio');
      if (Uuid::isValid($id)) {
        $result = $repository->find($id);
      } else {
        $result = $repository->findOneBy(['identifier' => $id]);
      }

      if ($result === null) {
        return $this->view(["Object not found"], Response::HTTP_NOT_FOUND);
      }

      if ($result->getStatus() == Servizio::STATUS_CANCELLED) {
        return $this->view(["You are not allowed to see this service"], Response::HTTP_FORBIDDEN);
      }

      return $this->view(
        $this->serviceDto->fromEntity($result, $this->formServerApiAdapterService->getFormServerPublicUrl()),
        Response::HTTP_OK
      );
    } catch (\Exception $e) {
      $this->logger->error(
        $e->getMessage(),
        ['request' => $request]
      );
      return $this->view(["Object not found"], Response::HTTP_NOT_FOUND);
    }
  }

  /**
   * Retrieve form Service schema
   * @Rest\Get("/{id}/form", name="form_service_api_get")
   *
   * @OA\Response(
   *     response=200,
   *     description="Retrieve service Form schma"
   * )
   *
   * @OA\Response(
   *     response=404,
   *     description="Form schema not found"
   * )
   * @OA\Tag(name="services")
   *
   * @param $id
   * @return View
   */
  public function getFormServiceAction($id): View
  {
    try {
      $repository = $this->getDoctrine()->getRepository('App\Entity\Servizio');
      /** @var Servizio $service */
      $service = $repository->find($id);
      if ($service === null) {
        return $this->view(["Object not found"], Response::HTTP_NOT_FOUND);
      }

      $response = $this->formServerApiAdapterService->getForm($service->getFormIoId());

      if ($response['status'] == 'success') {
        return $this->view($response['form'], Response::HTTP_OK);
      } else {
        return $this->view(["Form not found"], Response::HTTP_NOT_FOUND);
      }
    } catch (\Exception $e) {
      return $this->view(["Service not found"], Response::HTTP_NOT_FOUND);
    }
  }


  /**
   * Create a Service
   * @Rest\Post(name="services_api_post")
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
   *             ref=@Model(type=Service::class, groups={"write"})
   *         )
   *     )
   * )
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
   *     response=201,
   *     description="Create a Service"
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
   * @OA\Tag(name="services")
   *
   * @param Request $request
   * @return View
   * @throws ReflectionException
   */
  public function postServiceAction(Request $request): View
  {
    $this->denyAccessUnlessGranted(['ROLE_ADMIN']);

    if (!$this->checkProtocolHandler($request)) {
      return $this->view(
        ["Unknown protocol handler, allowed handlers are: " . implode(', ', $this->handlerList)],
        Response::HTTP_BAD_REQUEST
      );
    }

    $serviceDto = new Service();
    $formOptions = ['locale' => $request->getLocale()];
    $form = $this->createForm('App\Form\ServizioFormType', $serviceDto, $formOptions);
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

      $this->serviceManager->checkServiceRelations($serviceDto);

      $service = $this->serviceDto->toEntity($serviceDto);
      $service->setPraticaFCQN('\App\Entity\FormIO');
      $service->setPraticaFlowServiceName('ocsdc.form.flow.formio');

      // Imposto l'ente in base all'istanza
      $ente = $this->is->getCurrentInstance();
      $service->setEnte($ente);

      // Erogatore
      $erogatore = new Erogatore();
      $erogatore->setName('Erogatore di ' . $service->getName() . ' per ' . $ente->getName());
      $erogatore->addEnte($ente);
      $this->em->persist($erogatore);
      $service->activateForErogatore($erogatore);

      $this->serviceManager->save($service);

    } catch (UniqueConstraintViolationException $e) {
      $data = [
        'type' => 'error',
        'title' => 'Duplicate key',
        'description' => 'A service with same name is already present',
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
        'description' => 'Contact technical support at support@opencontent.it',
      ];
      $this->logger->error(
        $e->getMessage(),
        ['request' => $request]
      );

      return $this->view($data, Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    return $this->view(
      $this->serviceDto->fromEntity($service, $this->formServerApiAdapterService->getFormServerPublicUrl()),
      Response::HTTP_CREATED
    );

  }

  /**
   * Edit full Service
   * @Rest\Put("/{id}", name="services_api_put")
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
   *             ref=@Model(type=Service::class, groups={"write"})
   *         )
   *     )
   * )
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
   *     description="Edit full Service"
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
   * @OA\Tag(name="services")
   *
   * @param $id
   * @param Request $request
   * @return View
   */
  public function putServiceAction($id, Request $request): View
  {
    $this->denyAccessUnlessGranted(['ROLE_ADMIN']);

    try {

      if (!$this->checkProtocolHandler($request)) {
        return $this->view(
          ["Unknown protocol handler, allowed handlers are: " . implode(', ', $this->handlerList)],
          Response::HTTP_BAD_REQUEST
        );
      }

      $repository = $this->getDoctrine()->getRepository('App\Entity\Servizio');
      $service = $repository->find($id);

      if (!$service) {
        return $this->view(["Object not found"], Response::HTTP_NOT_FOUND);
      }
      //$serviceDto = $this->serviceDto->fromEntity($service);
      $serviceDto = new Service();
      $formOptions = ['locale' => $request->getLocale()];
      $form = $this->createForm('App\Form\ServizioFormType', $serviceDto, $formOptions);
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

      $this->serviceManager->checkServiceRelations($serviceDto);
      $service = $this->serviceDto->toEntity($serviceDto, $service);

      $this->serviceManager->save($service);
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
   * Patch a Service
   * @Rest\Patch("/{id}", name="services_api_patch")
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
   *             ref=@Model(type=Service::class, groups={"write"})
   *         )
   *    )
   * )
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
   *     description="Patch a Service"
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
   * @OA\Tag(name="services")
   *
   * @param $id
   * @param Request $request
   * @return View
   */
  public function patchServiceAction($id, Request $request): View
  {
    $this->denyAccessUnlessGranted(['ROLE_ADMIN']);

    try {

      if (!$this->checkProtocolHandler($request)) {
        return $this->view(
          ["Unknown protocol handler, allowed handlers are: " . implode(', ', $this->handlerList)],
          Response::HTTP_BAD_REQUEST
        );
      }

      $repository = $this->getDoctrine()->getRepository('App\Entity\Servizio');
      /** @var Servizio $service */
      $service = $repository->find($id);

      if (!$service) {
        return $this->view(["Object not found"], Response::HTTP_NOT_FOUND);
      }

      $serviceDto = $this->serviceDto->fromEntity($service, $this->formServerApiAdapterService->getFormServerPublicUrl());
      $formOptions = ['locale' => $request->getLocale()];
      $form = $this->createForm('App\Form\ServizioFormType', $serviceDto, $formOptions);
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

      $this->serviceManager->checkServiceRelations($serviceDto);
      $service = $this->serviceDto->toEntity($serviceDto, $service);
      $this->serviceManager->save($service);
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
   * Delete a Service
   * @Rest\Delete("/{id}", name="services_api_delete")
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
   * @OA\Tag(name="services")
   *
   * @Method("DELETE")
   */
  public function deleteAction($id): View
  {
    $this->denyAccessUnlessGranted(['ROLE_ADMIN']);

    $service = $this->em->getRepository('App\Entity\Servizio')->find($id);
    if ($service) {
      $this->em->remove($service);
      $this->em->flush();
    }

    return $this->view(null, Response::HTTP_NO_CONTENT);
  }

  /**
   * @param Request $request
   * @param FormInterface $form
   */
  private function processForm(Request $request, FormInterface $form)
  {
    $data = ServiceDto::normalizeData(json_decode($request->getContent(), true));
    $clearMissing = $request->getMethod() != 'PATCH';
    $form->submit($data, $clearMissing);
  }

  private function checkProtocolHandler(Request $request): bool
  {
    if ($request->get('protocol_handler') && !in_array($request->get('protocol_handler'), $this->handlerList)) {
      return false;
    }

    return true;
  }

  /**
   * Retrieve a Service public attachment
   * @Rest\Get("/{id}/attachments/{attachmentType}/{filename}", name="service_api_attachment_get")
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
    $repository = $this->getDoctrine()->getRepository('App\Entity\Servizio');
    /** @var Servizio $service */
    $service = $repository->find($id);

    if (!$service) {
      return $this->view(["Service not found"], Response::HTTP_NOT_FOUND);
    }

    if (!in_array($attachmentType, [PublicFile::CONDITIONS_TYPE, PublicFile::COSTS_TYPE])) {
      $this->logger->error("Invalid type $attachmentType");
      return $this->view(["Invalid type: $attachmentType is not supported"], Response::HTTP_BAD_REQUEST);
    }

    if ($attachmentType === PublicFile::CONDITIONS_TYPE) {
      $attachment = $service->getConditionAttachmentByName($filename);
    } elseif ($attachmentType === PublicFile::COSTS_TYPE) {
      $attachment = $service->getCostAttachmentByName($filename);
    } else {
      $attachment = null;
    }

    if (!$attachment) {
      return $this->view(["$filename not found"], Response::HTTP_NOT_FOUND);
    }

    try {
      return $fileService->download($attachment->getOriginalName(), $service, $attachmentType);
    } catch (FileNotFoundException $e) {
      $this->logger->error("Attachment $filename not found for type $attachmentType");
      return $this->view(["$filename not found"], Response::HTTP_NOT_FOUND);
    }
  }

}
