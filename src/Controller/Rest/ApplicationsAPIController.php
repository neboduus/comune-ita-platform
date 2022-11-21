<?php

namespace App\Controller\Rest;


use App\Dto\ApplicationDto;
use App\Entity\AllegatoOperatore;
use App\Entity\CPSUser;
use App\Entity\FormIO;
use App\Entity\Message as MessageEntity;
use App\Entity\OperatoreUser;
use App\Entity\Pratica;
use App\Entity\RispostaOperatore;
use App\Entity\Servizio;
use App\Entity\StatusChange;
use App\Model\Application;
use App\Model\File as FileModel;
use App\Model\LinksPagedList;
use App\Model\MetaPagedList;
use App\Model\PaymentOutcome;
use App\Model\Transition;
use App\Security\Voters\ApplicationVoter;
use App\Services\FileService\AllegatoFileService;
use App\Services\FormServerApiAdapterService;
use App\Services\InstanceService;
use App\Services\Manager\PraticaManager;
use App\Services\ModuloPdfBuilderService;
use App\Services\PaymentService;
use App\Services\PraticaStatusService;
use App\Utils\FormUtils;
use App\Utils\StringUtils;
use App\Utils\UploadedBase64File;
use DateTime;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\DBAL\Exception\DriverException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\ORMException;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use JMS\Serializer\SerializerBuilder;
use League\Csv\Exception;
use League\Flysystem\FileNotFoundException;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use OpenApi\Annotations as OA;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Exception\ValidatorException;

/**
 * Class ApplicationsAPIController
 * @Route("/applications")
 */
class ApplicationsAPIController extends AbstractFOSRestController
{

  const TRANSITION_SUBMIT = [
    'action' => 'submit',
    'description' => 'Submit Application',
  ];

  const TRANSITION_REGISTER = [
    'action' => 'register',
    'description' => 'Register Application',
  ];

  const TRANSITION_ASSIGN = [
    'action' => 'assign',
    'description' => 'Assign Application',
  ];

  const TRANSITION_REQUEST_INTEGRATION = [
    'action' => 'request-integration',
    'description' => 'Request integration',
  ];

  const TRANSITION_REGISTER_INTEGRATION_REQUEST = [
    'action' => 'register-integration-request',
    'description' => 'Register integration request',
  ];

  const TRANSITION_ACCEPT_INTEGRATION = [
    'action' => 'accept-integration',
    'description' => 'Accept integration',
  ];

  const TRANSITION_REGISTER_INTEGRATION_ANSWER = [
    'action' => 'register-integration-answer',
    'description' => 'Register integration answer',
  ];

  const TRANSITION_ACCEPT = [
    'action' => 'accept',
    'description' => 'Accept Application',
  ];

  const TRANSITION_REJECT = [
    'action' => 'reject',
    'description' => 'Reject Application',
  ];

  const TRANSITION_WITHDRAW = [
    'action' => 'withdraw',
    'description' => 'Withdraw Application',
  ];

  /** @var EntityManagerInterface */
  private $em;

  /** @var InstanceService */
  private $is;

  /** @var PraticaStatusService */
  private $statusService;

  /** @var ModuloPdfBuilderService */
  protected $pdfBuilder;

  protected $router;

  protected $baseUrl = '';

  /** @var LoggerInterface */
  protected $logger;

  /**
   * @var PraticaManager
   */
  private $praticaManager;
  /**
   * @var FormServerApiAdapterService
   */
  private $formServerService;

  /**
   * @var AllegatoFileService
   */
  private $fileService;

  /**
   * @var ApplicationDto
   */
  private $applicationDto;

  /**
   * @var PaymentService
   */
  private $paymentService;

  /**
   * ApplicationsAPIController constructor.
   * @param EntityManagerInterface $em
   * @param InstanceService $is
   * @param PraticaStatusService $statusService
   * @param ModuloPdfBuilderService $pdfBuilder
   * @param UrlGeneratorInterface $router
   * @param LoggerInterface $logger
   * @param PraticaManager $praticaManager
   * @param FormServerApiAdapterService $formServerService
   * @param AllegatoFileService $fileService
   * @param ApplicationDto $applicationDto
   * @param PaymentService $paymentService
   */
  public function __construct(
    EntityManagerInterface $em,
    InstanceService $is,
    PraticaStatusService $statusService,
    ModuloPdfBuilderService $pdfBuilder,
    UrlGeneratorInterface $router,
    LoggerInterface $logger,
    PraticaManager $praticaManager,
    FormServerApiAdapterService $formServerService,
    AllegatoFileService $fileService,
    ApplicationDto $applicationDto,
    PaymentService $paymentService
  ) {
    $this->em = $em;
    $this->is = $is;
    $this->statusService = $statusService;
    $this->pdfBuilder = $pdfBuilder;
    $this->router = $router;
    $this->baseUrl = $this->router->generate('applications_api_list', [], UrlGeneratorInterface::ABSOLUTE_URL);
    $this->logger = $logger;
    $this->praticaManager = $praticaManager;
    $this->formServerService = $formServerService;
    $this->fileService = $fileService;
    $this->applicationDto = $applicationDto;
    $this->paymentService = $paymentService;
  }

  /**
   * List all Applications
   *
   * @Rest\Get("", name="applications_api_list")
   *
   * @Security(name="Bearer")
   *
   * @OA\Parameter(
   *      name="version",
   *      in="query",
   *      @OA\Schema(
   *          type="string"
   *      ),
   *      required=false,
   *      description="Version of Api, default 1. From version 2 data field keys are exploded in a json object instead of version 1.* where are flattened strings"
   * )
   *
   * @OA\Parameter(
   *      name="service",
   *      in="query",
   *      @OA\Schema(
   *          type="string"
   *      ),
   *      required=false,
   *      description="Slug of the service"
   * )
   *
   * @OA\Parameter(
   *      name="order",
   *      in="query",
   *      @OA\Schema(
   *          type="string"
   *      ),
   *      required=false,
   *      description="Order field. Default creationTime"
   * )
   *
   * @OA\Parameter(
   *      name="sort",
   *      in="query",
   *      @OA\Schema(
   *          type="string"
   *      ),
   *      required=false,
   *      description="Sorting criteria of the order field. Default ASC"
   * )
   *
   * @OA\Parameter(
   *      name="createdAt[after|before|strictly_after|strictly_before]",
   *      in="query",
   *      @OA\Schema(
   *          type="string"
   *      ),
   *      required=false,
   *      description="Created at filter, format yyyy-mm-dd or yyyy-mm-ddTHH:ii:ssP"
   * )
   *
   * @OA\Parameter(
   *      name="updatedAt[after|before|strictly_after|strictly_before]",
   *      in="query",
   *      @OA\Schema(
   *          type="string"
   *      ),
   *      required=false,
   *      description="Updated at filter, format yyyy-mm-dd or yyyy-mm-ddTHH:ii:ssP"
   * )
   *
   * @OA\Parameter(
   *      name="submittedAt[after|before|strictly_after|strictly_before]",
   *      in="query",
   *      @OA\Schema(
   *          type="string"
   *      ),
   *      required=false,
   *      description="Submitted at filter, format yyyy-mm-dd or yyyy-mm-ddTHH:ii:ssP"
   * )
   *
   * @OA\Parameter(
   *      name="status",
   *      in="query",
   *      @OA\Schema(
   *          type="string"
   *      ),
   *      required=false,
   *      description="Status code of application"
   * )
   *
   * @OA\Parameter(
   *      name="offset",
   *      in="query",
   *      @OA\Schema(
   *          type="string"
   *      ),
   *      required=false,
   *      description="Offset of the query"
   * )
   *
   * @OA\Parameter(
   *      name="limit",
   *      in="query",
   *      @OA\Schema(
   *          type="string"
   *      ),
   *      required=false,
   *      description="Limit of the query"
   * )
   *
   * @OA\Response(
   *     response=200,
   *     description="Retrieve list of applications",
   *     @OA\JsonContent(
   *         type="object",
   *         @OA\Property(property="meta", type="object", ref=@Model(type=MetaPagedList::class)),
   *         @OA\Property(property="links", type="object", ref=@Model(type=LinksPagedList::class)),
   *         @OA\Property(property="data", type="array", @OA\Items(ref=@Model(type=Application::class, groups={"read"})))
   *     )
   * )
   *
   * @OA\Response(
   *     response=403,
   *     description="Access denied"
   * )
   *
   * @OA\Tag(name="applications")
   */

  public function getApplicationsAction(Request $request)
  {
    $this->denyAccessUnlessGranted(['ROLE_CPS_USER', 'ROLE_OPERATORE', 'ROLE_ADMIN']);

    $offset = intval($request->get('offset', 0));
    $limit = intval($request->get('limit', 10));
    $version = intval($request->get('version', 1));

    $serviceParameter = $request->get('service', false);
    $statusParameter = $request->get('status', false);
    $createdAtParameter = $request->get('createdAt', false);
    $updatedAtParameter = $request->get('updatedAt', false);
    $submittedAtParameter = $request->get('submittedAt', false);

    $orderParameter = $request->get('order', false);
    $sortParameter = $request->get('sort', false);

    if ($limit > 100) {
      return $this->view(["Limit parameter is too high"], Response::HTTP_BAD_REQUEST);
    }

    $queryParameters = ['offset' => $offset, 'limit' => $limit];
    if ($serviceParameter) {
      $queryParameters['service'] = $serviceParameter;
    }
    if ($statusParameter) {
      $queryParameters['status'] = $statusParameter;
    }
    if ($orderParameter) {
      $queryParameters['order'] = $orderParameter;
    }
    if ($sortParameter) {
      $queryParameters['sort'] = $sortParameter;
    }

    $dateFormat = 'Y-m-d';
    $datetimeFormat = DATE_ATOM;

    if ($createdAtParameter) {
      foreach ($createdAtParameter as $v) {
        $date = DateTime::createFromFormat($dateFormat, $v) ?: DateTime::createFromFormat($datetimeFormat, $v);
        if (!$date || ($date->format($dateFormat) !== $v && $date->format($datetimeFormat) !== $v)) {
          return $this->view(
            ["Parameter createdAt must be in on of these formats: yyyy-mm-dd or yyyy-mm-ddTHH:ii:ssP"],
            Response::HTTP_BAD_REQUEST
          );
        }
      }
      $queryParameters['createdAt'] = $createdAtParameter;
    }

    if ($updatedAtParameter) {
      foreach ($updatedAtParameter as $v) {
        $date = DateTime::createFromFormat($dateFormat, $v) ?: DateTime::createFromFormat($datetimeFormat, $v);

        if (!$date || ($date->format($dateFormat) !== $v && $date->format($datetimeFormat) !== $v)) {
          return $this->view(
            ["Parameter updatedAt must be in on of these formats: yyyy-mm-dd or yyyy-mm-ddTHH:ii:ssP"],
            Response::HTTP_BAD_REQUEST
          );
        }
      }
      $queryParameters['updatedAt'] = $updatedAtParameter;
    }

    if ($submittedAtParameter) {
      foreach ($submittedAtParameter as $v) {
        $date = DateTime::createFromFormat($dateFormat, $v) ?: DateTime::createFromFormat($datetimeFormat, $v);
        if (!$date || ($date->format($dateFormat) !== $v && $date->format($datetimeFormat) !== $v)) {
          return $this->view(
            ["Parameter submittedAt must be in on of these formats: yyyy-mm-dd or yyyy-mm-ddTHH:ii:ssP"],
            Response::HTTP_BAD_REQUEST
          );
        }
      }
      $queryParameters['submittedAt'] = $submittedAtParameter;
    }

    if ($statusParameter) {
      $applicationStatuses = array_keys(Pratica::getStatuses());
      if (!in_array($statusParameter, $applicationStatuses)) {
        return $this->view(
          ["Status code not present, chose one between: ".implode(',', $applicationStatuses)],
          Response::HTTP_BAD_REQUEST
        );
      }
    }

    $user = $this->getUser();
    $repositoryService = $this->em->getRepository('App\Entity\Servizio');
    $allowedServices = $this->getAllowedServices();

    if (empty($allowedServices) && $user instanceof OperatoreUser) {
      return $this->view(["You are not allowed to view applications"], Response::HTTP_FORBIDDEN);
    }

    if ($serviceParameter) {
      $service = $repositoryService->findOneBy(['slug' => $serviceParameter]);
      if (!$service instanceof Servizio) {
        return $this->view(["Service not found"], Response::HTTP_NOT_FOUND);
      }
      if (!empty($allowedServices) && !in_array($service->getId(), $allowedServices)) {
        return $this->view(["You are not allowed to view applications of passed service"], Response::HTTP_FORBIDDEN);
      }
      $allowedServices = [$service->getId()];
      $queryParameters['service'] = $serviceParameter;
    }

    $result = [];
    $result['meta']['parameter'] = $queryParameters;
    $repoApplications = $this->em->getRepository(Pratica::class);

    try {
      $parameters = $queryParameters;
      if (!empty($allowedServices)) {
        $parameters['service'] = $allowedServices;
      }
      $user = $this->getUser();
      if ($user instanceof CPSUser) {
        $parameters['user'] = $user->getId();
      }
      $count = $repoApplications->getApplications($parameters, true);

    } catch (NoResultException $e) {
      $count = 0;
    } catch (NonUniqueResultException $e) {
      return $this->view($e->getMessage(), Response::HTTP_I_AM_A_TEAPOT);
    }

    $result['meta']['count'] = $count;
    $result['links']['self'] = $this->generateUrl(
      'applications_api_list',
      $queryParameters,
      UrlGeneratorInterface::ABSOLUTE_URL
    );
    $result['links']['prev'] = null;
    $result['links']['next'] = null;
    $result ['data'] = [];

    if ($offset != 0) {
      $queryParameters['offset'] = $offset - $limit;
      $result['links']['prev'] = $this->generateUrl(
        'applications_api_list',
        $queryParameters,
        UrlGeneratorInterface::ABSOLUTE_URL
      );
    }

    if ($offset + $limit < $count) {
      $queryParameters['offset'] = $offset + $limit;
      $result['links']['next'] = $this->generateUrl(
        'applications_api_list',
        $queryParameters,
        UrlGeneratorInterface::ABSOLUTE_URL
      );
    }
    $order = $orderParameter ?: "creationTime";
    $sort = $sortParameter ?: "ASC";

    try {
      $applications = $repoApplications->getApplications($parameters, false, $order, $sort, $offset, $limit);

      foreach ($applications as $s) {
        $result ['data'][] = $this->applicationDto->fromEntity($s);
      }

      return $this->view($result, Response::HTTP_OK);
    } catch (\Exception $exception) {
      return $this->view($exception->getMessage(), Response::HTTP_BAD_REQUEST);
    }
  }


  /**
   * Retrieve an Application
   * @Rest\Get("/{id}", name="application_api_get")
   *
   * @OA\Parameter(
   *      name="version",
   *      in="query",
   *      @OA\Schema(
   *          type="string"
   *      ),
   *      required=false,
   *      description="Version of Api, default 1. From version 2 data field keys are exploded in a json object instead of version 1.* where are flattened strings"
   *  )
   *
   * @OA\Response(
   *     response=200,
   *     description="Retrieve an Application",
   *     @Model(type=Application::class, groups={"read"})
   * )
   *
   * @OA\Response(
   *     response=403,
   *     description="Access denied"
   * )
   *
   * @OA\Response(
   *     response=404,
   *     description="Application not found"
   * )
   * @OA\Tag(name="applications")
   *
   * @param $id
   * @param Request $request
   * @return View
   */
  public function getApplicationAction($id, Request $request)
  {

    $version = intval($request->get('version', 1));

    try {
      $repository = $this->em->getRepository('App\Entity\Pratica');
      /** @var Pratica $result */
      $result = $repository->find($id);
      if ($result === null) {
        return $this->view(["Application not found"], Response::HTTP_NOT_FOUND);
      }

      $this->denyAccessUnlessGranted(ApplicationVoter::VIEW, $result);
      $data = $this->applicationDto->fromEntity($result, true, $version);

      return $this->view($data, Response::HTTP_OK);
    } catch (\Exception $e) {
      return $this->view(["Identifier conversion error"], Response::HTTP_BAD_REQUEST);
    }
  }

  /**
   * Create an Application
   * @Rest\Post(name="applications_api_post")
   *
   * @Security(name="Bearer")
   *
   * @OA\RequestBody(
   *     description="The application to create",
   *     required=true,
   *     @OA\MediaType(
   *         mediaType="application/json",
   *         @OA\Schema(
   *             type="object",
   *             ref=@Model(type=Application::class, groups={"write"})
   *         )
   *     )
   * )
   *
   * @OA\Response(
   *     response=201,
   *     description="Create an Application"
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
   * @OA\Tag(name="applications")
   *
   * @param Request $request
   * @return View
   */
  public function postApplicationAction(Request $request)
  {
    $this->denyAccessUnlessGranted(['ROLE_CPS_USER', 'ROLE_OPERATORE', 'ROLE_ADMIN']);

    $applicationModel = new Application();
    $form = $this->createForm('App\Form\Rest\ApplicationFormType', $applicationModel);
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
      $service = $this->em->getRepository('App\Entity\Servizio')->find($applicationModel->getService());
      if (!$service instanceof Servizio) {
        return $this->view(["Service not found"], Response::HTTP_BAD_REQUEST);
      }
    } catch (DriverException $e) {
      return $this->view(["Service uuid is not formally correct"], Response::HTTP_BAD_REQUEST);
    }

    $result = $this->formServerService->getFormSchema($service->getFormIoId());
    if ($result['status'] != 'success') {
      return $this->view(["There was an error on retrieve form schema"], Response::HTTP_BAD_REQUEST);
    }
    $schema = $result['schema'];
    $this->praticaManager->setSchema($schema);
    $flatSchema = $this->praticaManager->arrayFlat($schema, true);
    $cleanedData = StringUtils::cleanData($applicationModel->getData());
    $flatData = $this->praticaManager->arrayFlat($cleanedData);

    if (empty($cleanedData)) {
      return $this->view(["Empty application are not allowed"], Response::HTTP_BAD_REQUEST);
    }

    foreach ($flatData as $k => $v) {
      // In caso di datagrid/editgrid lo schema del form contiene un array per cui la chiave contiene campi del tipo
      // api_key_datagrid.0.api_key_interno. La submission contiene invece una array con i dati inseriti dall'utente nel
      // datagrid quindi api_key_datagrid.0.api_key_interno, api_key_datagrid.1.api_key_interno, api_key_datagrid.2.api_key_interno.
      $k = preg_replace('/\.\d+\./', '.0.', $k);

      // Todo: creare servizio più efficace per controllo conformità schema
      if ($flatSchema[$k.'.type'] != 'file') {
        if (!isset($flatSchema[$k.'.type'])) {
          return $this->view(["Service's schema does not match data sent"], Response::HTTP_BAD_REQUEST);
        }
      }
    }

    $data = [
      'data' => [],
      'flattened' => [],
      'schema' => $flatSchema,
    ];

    if (!empty($cleanedData)) {
      $data['data'] = $cleanedData;
      $data['flattened'] = $flatData;
    }

    if ($this->getUser() instanceof CPSUser) {
      $user = $this->getUser();
      try {
        $this->praticaManager->validateUserData($flatData, $user);
      } catch (\Exception $e) {
        $data = [
          'type' => 'error',
          'title' => 'There was an error during save process',
          'description' => $e->getMessage(),
        ];
        return $this->view($data, Response::HTTP_BAD_REQUEST);
      }
    } else {
      try {
        $user = $this->em->getRepository('App\Entity\CPSUser')->find($applicationModel->getUser());
        if (!$user instanceof CPSUser) {
          $user = $this->praticaManager->checkUser($data);
        }
      } catch (DriverException $e) {
        $user = $this->praticaManager->checkUser($data);
      } catch (ORMException $e) {
        $user = $this->praticaManager->checkUser($data);
      } catch (\Exception $e) {
        $this->logger->error($e->getMessage());
        return $this->view(["Something is wrong"], Response::HTTP_INTERNAL_SERVER_ERROR);
      }
    }

    try {

      $statusChange = null;
      if ($user != $this->getUser()) {
        $statusChange = new StatusChange();
        $statusChange->setEvento('Creazione pratica da altro soggetto.');
        $statusChange->setOperatore($this->getUser()->getFullName());
      }

      /** @var FormIO $pratica */
      $pratica = $this->applicationDto->toEntity($applicationModel, new FormIO());
      $pratica->setUser($user);
      $pratica->setEnte($this->is->getCurrentInstance());
      $pratica->setServizio($service);
      $pratica->setStatus($applicationModel->getStatus(), $statusChange);
      $this->praticaManager->validateDematerializedData($data, $pratica);
      $pratica->setDematerializedForms($data);
      if ($pratica->getStatus() > Pratica::STATUS_DRAFT) {
        $pratica->setSubmissionTime(time());
      }
      $this->praticaManager->addAttachmentsToApplication($pratica, $flatData);
      $this->em->persist($pratica);
      $this->em->flush();

      if ($pratica->getStatus() > Pratica::STATUS_DRAFT) {
        if ($applicationModel->getStatus() == Pratica::STATUS_PRE_SUBMIT) {
          $this->pdfBuilder->createForPraticaAsync($pratica, Pratica::STATUS_SUBMITTED);
        } else {
          $this->pdfBuilder->createForPraticaAsync($pratica, $applicationModel->getStatus());
        }
      }
    } catch (ValidatorException $e) {
      $data = [
        'type' => 'error',
        'title' => 'There was an error during data validation',
        'description' => $e->getMessage(),
      ];
      $this->logger->error($e->getMessage(), ['request' => $request]);

      return $this->view($data, Response::HTTP_BAD_REQUEST);
    } catch (\Exception $e) {
      $data = [
        'type' => 'error',
        'title' => 'There was an error during save process',
        'description' => 'Contact technical support at support@opencontent.it',
      ];
      $this->logger->error($e->getMessage(), ['request' => $request]);

      return $this->view($data, Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    return $this->view($this->applicationDto->fromEntity($pratica), Response::HTTP_CREATED);
  }

  /**
   * Retrieve backoffice data of an application
   * @Rest\Get("/{id}/backoffice", name="application_backoffice_api_get")
   *
   *
   * @OA\Parameter(
   *      name="version",
   *      in="query",
   *      @OA\Schema(
   *          type="string"
   *      ),
   *      required=false,
   *      description="Version of Api, default 1. From version 2 data field keys are exploded in a json objet instead of version 1.* the are flattened strings"
   *  )
   *
   * @OA\Response(
   *     response=200,
   *     description="Retrieve backoffice data of an application",
   *     @OA\Schema(
   *         type="object",
   *         @OA\Property(property="backoffice_data", type="object")
   *     )
   * )
   *
   * @OA\Response(
   *     response=403,
   *     description="Access denied"
   * )
   *
   * @OA\Response(
   *     response=404,
   *     description="Application not found"
   * )
   * @OA\Tag(name="applications")
   *
   * @param $id
   * @param Request $request
   * @return View
   */
  public function getApplicationBackofficeDataAction($id, Request $request)
  {
    $version = intval($request->get('version', 1));

    try {
      $repository = $this->em->getRepository('App\Entity\Pratica');
      /** @var Pratica $result */
      $result = $repository->find($id);
      if ($result === null) {
        return $this->view(["Application not found"], Response::HTTP_NOT_FOUND);
      }

      $this->denyAccessUnlessGranted(ApplicationVoter::VIEW, $result);

      $allowedServices = $this->getAllowedServices();
      if (!in_array($result->getServizio()->getId(), $allowedServices)) {
        return $this->view(["You are not allowed to view this application"], Response::HTTP_FORBIDDEN);
      }

      $data = $this->applicationDto->fromEntity($result, true, $version);

      return $this->view([
        'backoffice_data' => $data->getBackofficeData(),
      ], Response::HTTP_OK);
    } catch (\Exception $e) {
      return $this->view(["Identifier conversion error"], Response::HTTP_BAD_REQUEST);
    }
  }

  /**
   * Add or update backoffice data an Application
   * @Rest\Put("/{id}/backoffice", name="applications_backoffice_api_put")
   * @Rest\Post("/{id}/backoffice", name="applications_backoffice_api_post")
   *
   * @Security(name="Bearer")
   *
   * @OA\RequestBody(
   *     description="The application to update",
   *     required=true,
   *     @OA\MediaType(
   *         mediaType="application/json",
   *         @OA\Schema(
   *             type="object",
   *             @OA\Property(property="backoffice_data", type="object")
   *         )
   *     )
   * )
   *
   * @OA\Response(
   *     response=201,
   *     description="Create or update backoffice data"
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
   * @OA\Tag(name="applications")
   *
   * @param Request $request
   * @return View
   */
  public function updateApplicationBackofficeDataAction($id, Request $request)
  {

    $repository = $this->em->getRepository('App\Entity\Pratica');
    /** @var Pratica $application */
    $application = $repository->find($id);
    if ($application === null) {
      throw new Exception('Application not found');
    }
    $this->denyAccessUnlessGranted(ApplicationVoter::EDIT, $application);

    $form = $this->createForm('App\Form\Rest\ApplicationBackofficeFormType');
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

    $service = $application->getServizio();

    $schema = null;
    $result = $this->formServerService->getFormSchema($service->getBackofficeFormId());
    if ($result['status'] == 'success') {
      $schema = $result['schema'];
    }

    $flatSchema = $this->praticaManager->arrayFlat($schema, true);
    $flatData = $this->praticaManager->arrayFlat($request->request->get('backoffice_data'));

    foreach ($flatData as $k => $v) {
      if (!isset($flatSchema[$k.'.type']) && !isset($flatSchema[$k])) {
        return $this->view(["Service's schema does not match data sent"], Response::HTTP_BAD_REQUEST);
      }
    }

    $data = [
      'data' => $request->request->get('backoffice_data'),
      'flattened' => $flatData,
      'schema' => $flatSchema,
    ];

    try {
      $application->setBackofficeFormData($data);
      $this->em->persist($application);
      $this->em->flush();

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

    return $this->view($this->applicationDto->fromEntity($application), Response::HTTP_CREATED);
  }

  /**
   * Retrieve application history
   * @Rest\Get("/{id}/history", name="application_api_get_history")
   *
   * @OA\Response(
   *     response=200,
   *     description="Retrieve application history",
   *     @OA\JsonContent(
   *         type="array",
   *         @OA\Items(ref=@Model(type=Transition::class))
   *     )
   * )
   *
   * @OA\Response(
   *     response=403,
   *     description="Access denied"
   * )
   *
   * @OA\Response(
   *     response=404,
   *     description="Application not found"
   * )
   * @OA\Tag(name="applications")
   *
   * @param $id
   * @param Request $request
   * @return View
   */
  public function getApplicationHistoryAction($id, Request $request): View
  {
    try {
      $repository = $this->em->getRepository('App\Entity\Pratica');
      $result = $repository->find($id);
      if ($result === null) {
        return $this->view(["Application not found"], Response::HTTP_NOT_FOUND);
      }
      $this->denyAccessUnlessGranted(ApplicationVoter::VIEW, $result);

      $data = $result->getHistory();

      return $this->view($data, Response::HTTP_OK);
    } catch (\Exception $e) {
      return $this->view(["Identifier conversion error"], Response::HTTP_BAD_REQUEST);
    }
  }

  /**
   * Retrieve an Applications attachment
   * @Rest\Get("/{id}/attachments/{attachmentId}", name="application_api_attachment_get")
   *
   * @OA\Response(
   *     response=200,
   *     description="Retrieve attachment file",
   * )
   *
   * @OA\Response(
   *     response=403,
   *     description="Access denied"
   * )
   *
   * @OA\Response(
   *     response=404,
   *     description="Attachment not found"
   * )
   * @OA\Tag(name="applications")
   *
   * @param $id
   * @return View|Response
   */
  public function attachmentAction($id, $attachmentId)
  {

    $repository = $this->em->getRepository('App\Entity\Allegato');
    $result = $repository->find($attachmentId);
    if ($result === null) {
      return $this->view(["Attachment not found"], Response::HTTP_NOT_FOUND);
    }
    $pratica = $this->em->getRepository('App\Entity\Pratica')->find($id);

    $this->denyAccessUnlessGranted(ApplicationVoter::VIEW, $pratica);

    if ($result->getType() === RispostaOperatore::TYPE_DEFAULT) {
      $fileContent = $this->pdfBuilder->renderForResponse($pratica);
      $filename = mb_convert_encoding($result->getFilename(), "ASCII", "auto");
      $response = new Response($fileContent);
      $disposition = $response->headers->makeDisposition(
        ResponseHeaderBag::DISPOSITION_ATTACHMENT,
        $filename
      );
    } else {
      try {
        $fileContent = $this->fileService->getAttachmentContent($result);
      } catch (FileNotFoundException $e) {
        return $this->view(["Attachment not found"], Response::HTTP_NOT_FOUND);
      }
      $filename = mb_convert_encoding($result->getFilename(), "ASCII", "auto");
      $response = new Response($fileContent);
      $disposition = $response->headers->makeDisposition(
        ResponseHeaderBag::DISPOSITION_ATTACHMENT,
        $filename
      );
    }

    $response->headers->set('Content-Disposition', $disposition);

    return $response;
  }

  /**
   * Retrieve an Application paymnet's info
   * @Rest\Get("/{id}/payment", name="application_api_payment_get")
   *
   * @OA\Parameter(
   *      name="test",
   *      in="query",
   *      @OA\Schema(
   *          type="string"
   *      ),
   *      required=false,
   *      description="Test parameter"
   *  )
   *
   * @OA\Response(
   *     response=200,
   *     description="Retrieve an Application"
   * )
   *
   * @OA\Response(
   *     response=403,
   *     description="Access denied"
   * )
   *
   * @OA\Response(
   *     response=404,
   *     description="Application not found"
   * )
   * @OA\Tag(name="applications")
   *
   * @param $id
   * @param Request $request
   * @return View
   */
  public function getApplicationPaymentAction($id, Request $request): View
  {

    try {
      $repository = $this->em->getRepository('App\Entity\Pratica');
      /** @var Pratica $result */
      $result = $repository->find($id);
      if ($result === null) {
        return $this->view(["Application not found"], Response::HTTP_NOT_FOUND);
      }
      $this->denyAccessUnlessGranted(ApplicationVoter::VIEW, $result);

      $data = $this->paymentService->getPaymentStatusByApplication($result);

      if (empty($data)) {
        return $this->view(["Payment data not found"], Response::HTTP_NOT_FOUND);
      }

      return $this->view($data, Response::HTTP_OK);
    } catch (\Exception $e) {
      $this->logger->error('Errer fetching payment of application: ' . $id . ' - ' . $e->getMessage());
      return $this->view(['Errer fetching payment of application: ' . $id ], Response::HTTP_BAD_REQUEST);
    }
  }


  /**
   * Update payment data of an application
   * @Route("/{id}/payment", name="applications_payment_api_post")
   * @Rest\Post("/{id}/payment", name="applications_payment_api_post")
   *
   * @Security(name="Bearer")
   *
   * @OA\RequestBody(
   *     description="Update payment data of an application",
   *     required=true,
   *     @OA\MediaType(
   *         mediaType="application/json",
   *         @OA\Schema(
   *             type="object",
   *             ref=@Model(type=PaymentOutcome::class)
   *         )
   *     )
   * )
   *
   * @OA\Response(
   *     response=200,
   *     description="Updated"
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
   *
   * @OA\Response(
   *     response=422,
   *     description="Unprocessable Entity"
   * )
   *
   * @OA\Tag(name="applications")
   *
   * @param $id
   * @param Request $request
   * @return View
   */
  public function postApplicationPaymentAction($id, Request $request): View
  {
    $repository = $this->em->getRepository('App\Entity\Pratica');
    /** @var Pratica $application */
    $application = $repository->find($id);

    if (!$application) {
      return $this->view(["Application not found"], Response::HTTP_NOT_FOUND);
    }

    $this->denyAccessUnlessGranted(ApplicationVoter::VIEW, $application);

    if (!in_array(
      $application->getStatus(),
      [Pratica::STATUS_PAYMENT_OUTCOME_PENDING, Pratica::STATUS_PAYMENT_PENDING]
    )) {
      return $this->view(["Application isn't in correct state"], Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    $paymentOutcome = new paymentOutcome();
    $form = $this->createForm('App\Form\PaymentOutcomeType', $paymentOutcome);
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

      $paymentData = $application->getPaymentData();
      $serializer = SerializerBuilder::create()->build();
      $paymentData['outcome'] = $serializer->toArray($paymentOutcome);
      $application->setPaymentData($paymentData);
      $this->em->persist($application);
      $this->em->flush();

      if ($paymentOutcome->getStatus() == 'OK') {
        $this->statusService->setNewStatus($application, Pratica::STATUS_PAYMENT_SUCCESS);
      } else {
        $this->statusService->setNewStatus($application, Pratica::STATUS_PAYMENT_ERROR);
      }

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

    return $this->view(["Application Payment Modified Successfully"], Response::HTTP_OK);
  }

  /**
   * Patch an Application
   * @Rest\Patch("/{id}", name="applications_api_patch")
   *
   * @Security(name="Bearer")
   *
   * @OA\RequestBody(
   *     description="The application to patch | Register integration request | Register integration answer",
   *     required=false,
   *     @OA\JsonContent(
   *         oneOf={
   *             @OA\Schema(
   *                 type="object",
   *                 ref=@Model(type=Application::class, groups={"write"})
   *             ),
   *             @OA\Schema(
   *                 type="object",
   *                 @OA\Property(property="integration_outbound_protocol_document_id", type="string", description="Integration request protocol number"),
   *                 @OA\Property(property="integration_outbound_protocol_number", type="string", description="Integration request protocol document id"),
   *                 @OA\Property(property="integration_outbound_protocolled_at", type="string", description="Integration request protocol date")
   *             ),
   *             @OA\Schema(
   *                 type="object",
   *                 @OA\Property(property="integration_inbound_protocol_document_id", type="string", description="Integration answer protocol number"),
   *                 @OA\Property(property="integration_inbound_protocol_number", type="string", description="Integration answer protocol document id"),
   *                 @OA\Property(property="integration_inbound_protocolled_at", type="string", description="Integration answer protocol date")
   *             )
   *         }
   *     )
   * )
   *
   * @OA\Response(
   *     response=200,
   *     description="Patch an Application"
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
   * @OA\Tag(name="applications")
   *
   * @param $id
   * @param Request $request
   * @return View
   */
  public function patchApplicationAction($id, Request $request): View
  {

    $repository = $this->em->getRepository('App\Entity\Pratica');
    /** @var Pratica $application */
    $application = $repository->find($id);

    if (!$application) {
      return $this->view(["Object not found"], Response::HTTP_NOT_FOUND);
    }

    $this->denyAccessUnlessGranted(ApplicationVoter::EDIT, $application);

    if (in_array(
      $application->getStatus(),
      [Pratica::STATUS_DRAFT, Pratica::STATUS_PAYMENT_OUTCOME_PENDING, Pratica::STATUS_PAYMENT_PENDING]
    )) {
      return $this->view(["Application isn't in correct state"], Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    // Todo: Passare alle transition prima possibile
    if ($application->getStatus() == Pratica::STATUS_REQUEST_INTEGRATION) {
      $this->forward(ApplicationsAPIController::class.'::applicationTransitionRegisterIntegrationRequestAction', [
        'id' => $application->getId(),
      ]);
    } elseif ($application->getStatus() == Pratica::STATUS_SUBMITTED_AFTER_INTEGRATION) {
      $this->forward(ApplicationsAPIController::class.'::applicationTransitionRegisterIntegrationAnswerAction', [
        'id' => $application->getId(),
      ]);
    }

    $applicationModel = $this->applicationDto->fromEntity($application);
    $form = $this->createForm('App\Form\ApplicationType', $applicationModel);
    $this->processForm($request, $form);

    if (!$form->isValid()) {
      $errors = FormUtils::getErrorsFromForm($form);
      $data = [
        'type' => 'validation_error',
        'title' => 'There was a validation error',
        'errors' => $errors,
      ];

      return $this->view($data, Response::HTTP_BAD_REQUEST);
    }

    try {

      // calcolo degli eventuali cambi stato prima di persistere
      $needChangeStateToRegistered = !$application->getNumeroProtocollo()
        && $application->getStatus() == Pratica::STATUS_SUBMITTED
        && $application->getServizio()->isProtocolRequired();

      $rispostaOperatore = $application->getRispostaOperatore();
      $needChangeStateToComplete = $rispostaOperatore
        && !$rispostaOperatore->getNumeroProtocollo()
        && $application->getStatus() == Pratica::STATUS_COMPLETE_WAITALLEGATIOPERATORE;

      $needChangeStateToCancelled = $rispostaOperatore
        && !$rispostaOperatore->getNumeroProtocollo()
        && $application->getStatus() == Pratica::STATUS_CANCELLED_WAITALLEGATIOPERATORE;

      // persist della patch
      $application = $this->applicationDto->toEntity($applicationModel, $application);
      $this->em->persist($application);
      $this->em->flush();

      // esecuzione degli eventuali cambi stato
      if ($needChangeStateToRegistered) {
        $this->statusService->setNewStatus($application, Pratica::STATUS_REGISTERED);
      }
      if ($needChangeStateToComplete) {
        $this->statusService->setNewStatus($application, Pratica::STATUS_COMPLETE);
      }
      if ($needChangeStateToCancelled) {
        $this->statusService->setNewStatus($application, Pratica::STATUS_CANCELLED);
      }

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
   * Submit an application
   * @Rest\Post("/{id}/transition/submit", name="application_api_post_transition_submit")
   *
   * @Security(name="Bearer")
   *
   * @OA\Response(
   *     response=204,
   *     description="Updated"
   * )
   *
   * @OA\Response(
   *     response=403,
   *     description="Access denied"
   * )
   *
   * @OA\Response(
   *     response=404,
   *     description="Application not found"
   * )
   * @OA\Tag(name="applications")
   *
   * @param $id
   * @param Request $request
   * @return View
   */
  public function applicationTransitionSubmitAction($id, Request $request): View
  {
    try {
      $repository = $this->em->getRepository('App\Entity\Pratica');
      $application = $repository->find($id);
      if ($application === null) {
        throw new Exception('Application not found');
      }
      $this->denyAccessUnlessGranted(ApplicationVoter::SUBMIT, $application);
      $this->praticaManager->finalizeSubmission($application);

    } catch (\Exception $e) {
      $data = [
        'type' => 'error',
        'title' => 'There was an error during transition process',
        'description' => 'Contact technical support at support@opencontent.it',
      ];
      $this->logger->error($e->getMessage(), ['request' => $request]);

      return $this->view($data, Response::HTTP_BAD_REQUEST);
    }

    return $this->view([], Response::HTTP_NO_CONTENT);
  }

  /**
   * Register an Application
   * @Rest\Post("/{id}/transition/register", name="application_api_post_transition_register")
   *
   * @Security(name="Bearer")
   *
   * @OA\RequestBody(
   *     description="The transition to create",
   *     required=true,
   *     @OA\MediaType(
   *         mediaType="application/json",
   *         @OA\Schema(
   *             type="object",
   *             @OA\Property(property="protocol_folder_number", type="string", description="Protocol folder number"),
   *             @OA\Property(property="protocol_folder_code", type="string", description="Protocol folder code"),
   *             @OA\Property(property="protocol_number", type="string", description="Protocol number"),
   *             @OA\Property(property="protocol_document_id", type="string", description="Protocol document id")
   *         )
   *     )
   * )
   *
   * @OA\Response(
   *     response=204,
   *     description="Updated"
   * )
   *
   * @OA\Response(
   *     response=403,
   *     description="Access denied"
   * )
   *
   * @OA\Response(
   *     response=404,
   *     description="Application not found"
   * )
   * @OA\Tag(name="applications")
   *
   * @param $id
   * @param Request $request
   * @return View
   */
  public function applicationTransitionRegisterAction($id, Request $request): View
  {
    try {
      $repository = $this->em->getRepository('App\Entity\Pratica');
      $application = $repository->find($id);
      if ($application === null) {
        return $this->view(["Application not found"], Response::HTTP_NOT_FOUND);
      }
      $this->denyAccessUnlessGranted(ApplicationVoter::EDIT, $application);

      if (!empty($application->getNumeroProtocollo())) {
        return $this->view(["Application already has a protocol number"], Response::HTTP_UNPROCESSABLE_ENTITY);
      }

      if (!$application->getServizio()->isProtocolRequired()) {
        return $this->view(["Application does not need to be protocolled"], Response::HTTP_UNPROCESSABLE_ENTITY);
      }

      $form = $this->createFormBuilder(null, ['allow_extra_fields' => true, 'csrf_protection' => false])
        ->add(
          'protocol_folder_number',
          TextType::class,
          [
            'constraints' => [
              new NotBlank(),
              new NotNull(),
            ],
          ]
        )
        ->add('protocol_folder_code', TextType::class)
        ->add(
          'protocol_number',
          TextType::class,
          [
            'constraints' => [
              new NotBlank(),
              new NotNull(),
            ],
          ]
        )
        ->add(
          'protocol_document_id',
          TextType::class,
          [
            'constraints' => [
              new NotBlank(),
              new NotNull(),
            ],
          ]
        )
        ->getForm();
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

      $data = $form->getData();
      $application->setNumeroFascicolo($data['protocol_folder_number']);
      $application->setCodiceFascicolo($data['protocol_folder_code']);
      $application->setNumeroProtocollo($data['protocol_number']);
      $application->setIdDocumentoProtocollo($data['protocol_document_id']);
      $this->statusService->setNewStatus($application, Pratica::STATUS_REGISTERED);

    } catch (\Exception $e) {
      $data = [
        'type' => 'error',
        'title' => 'There was an error during transition process',
        'description' => 'Contact technical support at support@opencontent.it',
      ];
      $this->logger->error($e->getMessage(), ['request' => $request]);

      return $this->view($data, Response::HTTP_BAD_REQUEST);
    }

    return $this->view([], Response::HTTP_NO_CONTENT);
  }

  /**
   * Register application outcome
   * @Rest\Post("/{id}/transition/register-outcome", name="application_api_post_transition_register_outcome")
   *
   * @Security(name="Bearer")
   *
   * @OA\RequestBody(
   *     description="The transition to create",
   *     required=true,
   *     @OA\MediaType(
   *         mediaType="application/json",
   *         @OA\Schema(
   *             type="object",
   *             @OA\Property(property="protocol_number", type="string", description="Outcome protocol number"),
   *             @OA\Property(property="protocol_document_id", type="string", description="Outcome protocol document id")
   *         )
   *     )
   * )
   *
   * @OA\Response(
   *     response=204,
   *     description="Updated"
   * )
   *
   * @OA\Response(
   *     response=403,
   *     description="Access denied"
   * )
   *
   * @OA\Response(
   *     response=404,
   *     description="Application not found"
   * )
   * @OA\Tag(name="applications")
   *
   * @param $id
   * @param Request $request
   * @return View
   */
  public function applicationTransitionRegisterOutcomeAction($id, Request $request): View
  {
    try {
      $repository = $this->em->getRepository('App\Entity\Pratica');
      /** @var Pratica $application */
      $application = $repository->find($id);
      if ($application === null) {
        return $this->view(["Application not found"], Response::HTTP_NOT_FOUND);
      }
      $this->denyAccessUnlessGranted(ApplicationVoter::EDIT, $application);

      $outcome = $application->getRispostaOperatore();
      if (empty($outcome)) {
        return $this->view(["Application doesn't has an outcome"], Response::HTTP_UNPROCESSABLE_ENTITY);
      }

      if (!empty($outcome->getNumeroProtocollo())) {
        return $this->view(["Outcome already has a protocol number"], Response::HTTP_UNPROCESSABLE_ENTITY);
      }

      if (!$application->getServizio()->isProtocolRequired()) {
        return $this->view(["Application does not need to be protocolled"], Response::HTTP_UNPROCESSABLE_ENTITY);
      }

      $form = $this->createFormBuilder(null, ['allow_extra_fields' => true, 'csrf_protection' => false])
        ->add(
          'protocol_number',
          TextType::class,
          [
            'constraints' => [
              new NotBlank(),
              new NotNull(),
            ],
          ]
        )
        ->add(
          'protocol_document_id',
          TextType::class,
          [
            'constraints' => [
              new NotBlank(),
              new NotNull(),
            ],
          ]
        )
        ->getForm();
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

      $data = $form->getData();
      $outcome->setNumeroProtocollo($data['protocol_number']);
      $outcome->setIdDocumentoProtocollo($data['protocol_document_id']);

      $this->em->persist($outcome);
      $this->em->flush();

      $application->addNumeroDiProtocollo([
        'id' => $outcome->getId(),
        'protocollo' => $data['protocol_document_id'],
      ]);

      if ($application->getEsito()) {
        $this->statusService->setNewStatus($application, Pratica::STATUS_COMPLETE);
      } else {
        $this->statusService->setNewStatus($application, Pratica::STATUS_CANCELLED);
      }

    } catch (\Exception $e) {
      $data = [
        'type' => 'error',
        'title' => 'There was an error during transition process',
        'description' => 'Contact technical support at support@opencontent.it',
      ];
      $this->logger->error($e->getMessage(), ['request' => $request]);

      return $this->view($data, Response::HTTP_BAD_REQUEST);
    }

    return $this->view([], Response::HTTP_NO_CONTENT);
  }

  /**
   * Assign an operator to an Application
   * @Rest\Post("/{id}/transition/assign", name="application_api_post_transition_assign")
   *
   * @Security(name="Bearer")
   *
   * @OA\Response(
   *     response=204,
   *     description="Updated"
   * )
   *
   * @OA\Response(
   *     response=403,
   *     description="Access denied"
   * )
   *
   * @OA\Response(
   *     response=404,
   *     description="Application not found"
   * )
   * @OA\Tag(name="applications")
   *
   * @param $id
   * @param Request $request
   * @return View
   */
  public function applicationTransitionAssignAction($id, Request $request): View
  {
    try {
      $repository = $this->em->getRepository('App\Entity\Pratica');
      $application = $repository->find($id);
      if ($application === null) {
        throw new Exception('Application not found');
      }
      $this->denyAccessUnlessGranted(ApplicationVoter::ASSIGN, $application);

      $this->praticaManager->assign($application, $this->getUser());

    } catch (\Exception $e) {
      $data = [
        'type' => 'error',
        'title' => 'There was an error during transition process',
        'description' => 'Contact technical support at support@opencontent.it',
      ];
      $this->logger->error($e->getMessage(), ['request' => $request]);

      return $this->view($data, Response::HTTP_BAD_REQUEST);
    }

    return $this->view([], Response::HTTP_NO_CONTENT);
  }

  /**
   * Answer to an application
   * @Rest\Post("/{id}/transition/accept", name="application_api_post_transition_accept")
   * @Rest\Post("/{id}/transition/reject", name="application_api_post_transition_reject")
   *
   * @Security(name="Bearer")
   *
   * @OA\RequestBody(
   *     description="The transition to create",
   *     required=true,
   *     @OA\MediaType(
   *         mediaType="application/json",
   *         @OA\Schema(
   *             type="object",
   *             @OA\Property(property="message", type="string", description="Application outcome"),
   *             @OA\Property(property="attachments", type="array", @OA\Items(ref=@Model(type=FileModel::class)))
   *         )
   *     )
   * )
   *
   *
   * @OA\Response(
   *     response=204,
   *     description="Updated"
   * )
   *
   * @OA\Response(
   *     response=403,
   *     description="Access denied"
   * )
   *
   * @OA\Response(
   *     response=404,
   *     description="Application not found"
   * )
   * @OA\Tag(name="applications")
   *
   * @param $id
   * @param Request $request
   * @return View
   */
  public function applicationTransitionOutcomeAction($id, Request $request): View
  {
    try {
      $repository = $this->em->getRepository('App\Entity\Pratica');
      $application = $repository->find($id);
      if ($application === null) {
        throw new Exception('Application not found');
      }
      $this->denyAccessUnlessGranted(ApplicationVoter::ACCEPT_OR_REJECT, $application);

      $defaultData = [
        'message' => null,
        'attachments' => null,
      ];
      $form = $this->createForm('App\Form\Rest\Transition\OutcomeFormType', $defaultData);
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

      $data = $form->getData();
      $application->setEsito($request->get('_route') == 'application_api_post_transition_accept');
      if ($data['message']) {
        $application->setMotivazioneEsito($data['message']);
      }

      foreach ($data['attachments'] as $attachment) {
        $base64Content = $attachment->getFile();
        $file = new UploadedBase64File($base64Content, $attachment->getMimeType());
        $allegato = new AllegatoOperatore();
        $allegato->setFile($file);
        $allegato->setOwner($application->getUser());
        $allegato->setDescription('Risposta Operatore');
        $allegato->setOriginalFilename($attachment->getName());
        $this->em->persist($allegato);
        $application->addAllegatoOperatore($allegato);
      }
      $this->praticaManager->finalize($application, $this->getUser());

    } catch (\Exception $e) {
      $data = [
        'type' => 'error',
        'title' => 'There was an error during transition process',
        'description' => 'Contact technical support at support@opencontent.it',
      ];
      $this->logger->error($e->getMessage(), ['request' => $request]);

      return $this->view($data, Response::HTTP_BAD_REQUEST);
    }

    return $this->view([], Response::HTTP_NO_CONTENT);
  }

  /**
   * Request integration on an application
   * @Rest\Post("/{id}/transition/request-integration", name="application_api_post_transition_request_integration")
   *
   * @Security(name="Bearer")
   *
   * @OA\RequestBody(
   *     description="The transition to create",
   *     required=true,
   *     @OA\MediaType(
   *         mediaType="application/json",
   *         @OA\Schema(
   *             type="object",
   *             @OA\Property(property="message", type="string", description="Reason of the integration request"),
   *             @OA\Property(property="attachments", type="array", @OA\Items(ref=@Model(type=FileModel::class)))
   *         )
   *     )
   * )
   *
   *
   * @OA\Response(
   *     response=204,
   *     description="Updated"
   * )
   *
   * @OA\Response(
   *     response=403,
   *     description="Access denied"
   * )
   *
   * @OA\Response(
   *     response=404,
   *     description="Application not found"
   * )
   * @OA\Tag(name="applications")
   *
   * @param $id
   * @param Request $request
   * @return View
   */
  public function applicationTransitionRequestIntegrationAction($id, Request $request): View
  {
    try {
      $repository = $this->em->getRepository('App\Entity\Pratica');
      $application = $repository->find($id);
      if ($application === null) {
        throw new Exception('Application not found');
      }
      $this->denyAccessUnlessGranted(ApplicationVoter::ACCEPT_OR_REJECT, $application);

      $defaultData = [
        'message' => null,
      ];

      $form = $this->createForm('App\Form\Rest\Transition\RequestIntegrationFormType', $defaultData);
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

      $data = $form->getData();
      $this->praticaManager->requestIntegration($application, $this->getUser(), $data);

    } catch (\Exception $e) {
      $data = [
        'type' => 'error',
        'title' => 'There was an error during transition process',
        'description' => $e->getMessage(),
      ];
      $this->logger->error($e->getMessage(), ['request' => $request]);

      return $this->view($data, Response::HTTP_BAD_REQUEST);
    }

    return $this->view([], Response::HTTP_NO_CONTENT);
  }

  /**
   * Accept integration on an application
   * @Rest\Post("/{id}/transition/accept-integration", name="application_api_post_transition_accept_integration")
   *
   * @Security(name="Bearer")
   *
   * @OA\RequestBody(
   *     description="Message's uuid to include in integration request response, accepted values: <ul><li>null - All messages added to applicaton from integration request will be added to the intergration request response</li><li>an empty array - No message will be added to the intergration request response</li><li>an array of message's uuid - Only messages with uuuid in array  will be added to the intergration request response</li></ul>",
   *     required=false,
   *     @OA\MediaType(
   *         mediaType="application/json",
   *         @OA\Schema(
   *             type="object",
   *             @OA\Property(property="messages", nullable=true, type="array", @OA\Items(type="string"))
   *         )
   *     )
   * )
   *
   * @OA\Response(
   *     response=204,
   *     description="Updated"
   * )
   *
   * @OA\Response(
   *     response=403,
   *     description="Access denied"
   * )
   *
   * @OA\Response(
   *     response=404,
   *     description="Application not found"
   * )
   * @OA\Tag(name="applications")
   *
   * @param $id
   * @param Request $request
   * @return View
   */
  public function applicationTransitionAcceptIntegrationAction($id, Request $request): View
  {
    try {
      $repository = $this->em->getRepository('App\Entity\Pratica');
      /** @var Pratica $application */
      $application = $repository->find($id);
      if ($application === null) {
        throw new Exception('Application not found');
      }
      $this->denyAccessUnlessGranted(ApplicationVoter::ACCEPT_OR_REJECT, $application);

      if ($application->getStatus() !== Pratica::STATUS_DRAFT_FOR_INTEGRATION) {
        throw new Exception('Application is not in the correct state');
      }

      $messages = null;
      $messagesID = $request->get('messages', []);
      if (!empty($messagesID)) {
        $messageRepository = $this->em->getRepository('App\Entity\Message');
        foreach ($messagesID as $id) {
          if (!Uuid::isValid($id)) {
            throw new Exception("$id not is a valid Uuid");
          }
          $message = $messageRepository->findOneBy([
            'id' => $id,
            'application' => $application->getId(),
          ]);
          if (!$message instanceof MessageEntity) {
            throw new Exception("Message $id not found");
          }
          $messages[] = $message;
        }
      }

      $this->praticaManager->acceptIntegration($application, $this->getUser(), $messages);

    } catch (\Exception $e) {
      $data = [
        'type' => 'error',
        'title' => 'There was an error during transition process',
        'description' => $e->getMessage(),
      ];
      $this->logger->error($e->getMessage(), ['request' => $request]);

      return $this->view($data, Response::HTTP_BAD_REQUEST);
    }

    return $this->view([], Response::HTTP_NO_CONTENT);
  }

  /**
   * Cancel integration request on an application
   * @Rest\Post("/{id}/transition/cancel-integration", name="application_api_post_transition_cancel_integration")
   *
   * @Security(name="Bearer")
   *
   *
   * @OA\Response(
   *     response=204,
   *     description="Updated"
   * )
   *
   * @OA\Response(
   *     response=403,
   *     description="Access denied"
   * )
   *
   * @OA\Response(
   *     response=404,
   *     description="Application not found"
   * )
   * @OA\Tag(name="applications")
   *
   * @param $id
   * @param Request $request
   * @return View
   */
  public function applicationTransitionCancelIntegrationAction($id, Request $request): View
  {
    try {
      $repository = $this->em->getRepository('App\Entity\Pratica');
      /** @var Pratica $application */
      $application = $repository->find($id);
      if ($application === null) {
        throw new Exception('Application not found');
      }
      $this->denyAccessUnlessGranted(ApplicationVoter::ACCEPT_OR_REJECT, $application);

      if ($application->getStatus() !== Pratica::STATUS_DRAFT_FOR_INTEGRATION) {
        throw new Exception('Application is not in the correct state');
      }

      $this->praticaManager->cancelIntegration($application, $this->getUser());

    } catch (\Exception $e) {
      $data = [
        'type' => 'error',
        'title' => 'There was an error during transition process',
        'description' => $e->getMessage(),
      ];
      $this->logger->error($e->getMessage(), ['request' => $request]);

      return $this->view($data, Response::HTTP_BAD_REQUEST);
    }

    return $this->view([], Response::HTTP_NO_CONTENT);
  }

  /**
   * Withdraw an application
   * @Rest\Post("/{id}/transition/withdraw", name="application_api_post_transition_withdraw")
   *
   * @Security(name="Bearer")
   *
   * @OA\Response(
   *     response=204,
   *     description="Updated"
   * )
   *
   * @OA\Response(
   *     response=403,
   *     description="Access denied"
   * )
   *
   * @OA\Response(
   *     response=404,
   *     description="Application not found"
   * )
   * @OA\Tag(name="applications")
   *
   * @param $id
   * @param Request $request
   * @return View
   */
  public function applicationTransitionWithDrawAction($id, Request $request): View
  {
    try {
      $repository = $this->em->getRepository('App\Entity\Pratica');
      $application = $repository->find($id);
      if ($application === null) {
        throw new Exception('Application not found');
      }

      $this->denyAccessUnlessGranted(ApplicationVoter::WITHDRAW, $application);

      $this->praticaManager->withdrawApplication($application, $this->getUser());
    } catch (\Exception $e) {
      $data = [
        'type' => 'error',
        'title' => 'There was an error during transition process',
        'description' => 'Contact technical support at support@opencontent.it',
      ];
      $this->logger->error($e->getMessage(), ['request' => $request]);

      return $this->view($data, Response::HTTP_BAD_REQUEST);
    }

    return $this->view([], Response::HTTP_NO_CONTENT);
  }

  /**
   * @param Request $request
   * @param FormInterface $form
   */
  private function processForm(Request $request, FormInterface $form)
  {
    $data = json_decode($request->getContent(), true);

    // Todo: find better way
    if (isset($data['data']) && count($data['data']) > 0) {
      $data['data'] = \json_encode($data['data']);
    } else {
      $data['data'] = \json_encode([]);
    }

    if (isset($data['backoffice_data']) && count($data['backoffice_data']) > 0) {
      $data['backoffice_data'] = \json_encode($data['backoffice_data']);
    } else {
      $data['backoffice_data'] = \json_encode([]);
    }

    $clearMissing = $request->getMethod() != 'PATCH';
    $form->submit($data, $clearMissing);
  }

  /**
   * Register application integration request
   * @Rest\Post("/{id}/transition/register-integration-request", name="application_api_post_transition_register_integration_request")
   *
   * @Security(name="Bearer")
   *
   * @OA\RequestBody(
   *     description="The transition to execute",
   *     required=true,
   *     @OA\MediaType(
   *         mediaType="application/json",
   *         @OA\Schema(
   *             type="object",
   *             @OA\Property(property="integration_outbound_protocol_document_id", type="string", description="Integration request protocol number"),
   *             @OA\Property(property="integration_outbound_protocol_number", type="string", description="Integration request protocol document id"),
   *             @OA\Property(property="integration_outbound_protocolled_at", type="string", format="date-time", description="Integration request protocol date")
   *         )
   *     )
   * )
   *
   * @OA\Response(
   *     response=204,
   *     description="Updated"
   * )
   *
   * @OA\Response(
   *     response=403,
   *     description="Access denied"
   * )
   *
   * @OA\Response(
   *     response=404,
   *     description="Application not found"
   * )
   * @OA\Tag(name="applications")
   *
   * @param $id
   * @param Request $request
   * @return View
   */
  public function applicationTransitionRegisterIntegrationRequestAction($id, Request $request): View
  {
    try {
      $repository = $this->em->getRepository('App\Entity\Pratica');
      /** @var Pratica $application */
      $application = $repository->find($id);
      if ($application === null) {
        return $this->view(["Application not found"], Response::HTTP_NOT_FOUND);
      }
      $this->denyAccessUnlessGranted(ApplicationVoter::EDIT, $application);

      if (!$application->getServizio()->isProtocolRequired()) {
        return $this->view(["Application does not need to be protocolled"], Response::HTTP_UNPROCESSABLE_ENTITY);
      }

      $form = $this->createFormBuilder(null, ['allow_extra_fields' => true, 'csrf_protection' => false])
        ->add(
          'integration_outbound_protocol_number',
          TextType::class,
          [
            'constraints' => [
              new NotBlank(),
              new NotNull(),
            ],
          ]
        )
        ->add(
          'integration_outbound_protocol_document_id',
          TextType::class,
          [
            'constraints' => [
              new NotBlank(),
              new NotNull(),
            ],
          ]
        )
        ->add('integration_outbound_protocolled_at', DateTimeType::class, [
          'widget' => 'single_text',
          'required' => false,
          'empty_data' => '',
        ])
        ->getForm();
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

      $data = $form->getData();
      $this->praticaManager->registerIntegrationRequest($application, $this->getUser(), $data);

    } catch (\Exception $e) {
      $data = [
        'type' => 'error',
        'title' => 'There was an error during transition process',
        'description' => 'Contact technical support at support@opencontent.it',
      ];
      $this->logger->error($e->getMessage(), ['request' => $request]);

      return $this->view($data, Response::HTTP_BAD_REQUEST);
    }

    return $this->view([], Response::HTTP_NO_CONTENT);
  }

  /**
   * Register application integration answer
   * @Rest\Post("/{id}/transition/register-integration-answer", name="application_api_post_transition_register_integration_answer")
   *
   * @Security(name="Bearer")
   *
   * @OA\RequestBody(
   *     description="The transition to execute",
   *     required=true,
   *     @OA\MediaType(
   *         mediaType="application/json",
   *         @OA\Schema(
   *             type="object",
   *             @OA\Property(property="integration_inbound_protocol_document_id", type="string", description="Integration answer protocol number"),
   *             @OA\Property(property="integration_inbound_protocol_number", type="string", description="Integration answer protocol document id"),
   *             @OA\Property(property="integration_inbound_protocolled_at", type="string", format="date-time", description="Integration answer protocol date")
   *         )
   *     )
   * )
   *
   * @OA\Response(
   *     response=204,
   *     description="Updated"
   * )
   *
   * @OA\Response(
   *     response=403,
   *     description="Access denied"
   * )
   *
   * @OA\Response(
   *     response=404,
   *     description="Application not found"
   * )
   * @OA\Tag(name="applications")
   *
   * @param $id
   * @param Request $request
   * @return View
   */
  public function applicationTransitionRegisterIntegrationAnswerAction($id, Request $request): View
  {
    try {
      $repository = $this->em->getRepository('App\Entity\Pratica');
      /** @var Pratica $application */
      $application = $repository->find($id);
      if ($application === null) {
        return $this->view(["Application not found"], Response::HTTP_NOT_FOUND);
      }
      $this->denyAccessUnlessGranted(ApplicationVoter::EDIT, $application);

      if (!$application->getServizio()->isProtocolRequired()) {
        return $this->view(["Application does not need to be protocolled"], Response::HTTP_UNPROCESSABLE_ENTITY);
      }

      $form = $this->createFormBuilder(null, ['allow_extra_fields' => true, 'csrf_protection' => false])
        ->add(
          'integration_inbound_protocol_document_id',
          TextType::class,
          [
            'constraints' => [
              new NotBlank(),
              new NotNull(),
            ],
          ]
        )
        ->add(
          'integration_inbound_protocol_number',
          TextType::class,
          [
            'constraints' => [
              new NotBlank(),
              new NotNull(),
            ],
          ]
        )
        ->add('integration_inbound_protocolled_at', DateTimeType::class, [
          'widget' => 'single_text',
          'required' => false,
          'empty_data' => '',
        ])
        ->getForm();
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

      $data = $form->getData();
      $this->praticaManager->registerIntegrationAnswer($application, $this->getUser(), $data);

    } catch (\Exception $e) {
      $data = [
        'type' => 'error',
        'title' => 'There was an error during transition process',
        'description' => 'Contact technical support at support@opencontent.it',
      ];
      $this->logger->error($e->getMessage(), ['request' => $request]);

      return $this->view($data, Response::HTTP_BAD_REQUEST);
    }

    return $this->view([], Response::HTTP_NO_CONTENT);
  }

  /**
   * Force change application status payment pending by to payment success
   * @Rest\Post("/{id}/transition/complete-payment", name="application_api_post_transition_complete_payment")
   *
   * @Security(name="Bearer")
   *
   * @OA\RequestBody(
   *     description="The message to create",
   *     required=true,
   *     @OA\MediaType(
   *         mediaType="application/json",
   *         @OA\Schema(
   *             type="object",
   *             @OA\Property(property="message", type="string", description="Text message"),
   *             @OA\Property(property="subject", type="string", description="Subject message text"),
   *             @OA\Property(property="visibility", type="boolean", description="Visibility of message")
   *         )
   *     )
   * )
   *
   * @OA\Response(
   *     response=204,
   *     description="Updated"
   * )
   *
   * @OA\Response(
   *     response=403,
   *     description="Access denied"
   * )
   *
   * @OA\Response(
   *     response=404,
   *     description="Application not found"
   * )
   * @OA\Tag(name="applications")
   *
   * @param $id
   * @param Request $request
   * @return View
   */
  public function applicationTransitionPaymentCompletedAction($id, Request $request): View
  {
    $user = $this->getUser();
    try {
      $repository = $this->em->getRepository('App\Entity\Pratica');
      $application = $repository->find($id);
      if (!$application) {
        return $this->view(["Application not found"], Response::HTTP_NOT_FOUND);
      }
      $this->denyAccessUnlessGranted(ApplicationVoter::ASSIGN, $application);
      if (!in_array(
        $application->getStatus(),
        [Pratica::STATUS_PAYMENT_OUTCOME_PENDING, Pratica::STATUS_PAYMENT_PENDING]
      )) {
        return $this->view(["Application isn't in correct state"], Response::HTTP_UNPROCESSABLE_ENTITY);
      }

      $message = json_decode($request->getContent(), true);
      $this->praticaManager->finalizePaymentCompleteSubmission($application, $user, $message);


    } catch (\Exception $e) {
      $data = [
        'type' => 'error',
        'title' => 'There was an error during transition process',
        'description' => 'Contact technical support at support@opencontent.it',
      ];
      $this->logger->error($e->getMessage(), ['request' => $request]);

      return $this->view($data, Response::HTTP_BAD_REQUEST);
    }

    return $this->view(["Application Status Payment Modified Successfully"], Response::HTTP_OK);

  }

  /**
   * @return array
   */
  private function getAllowedServices(): array
  {
    $user = $this->getUser();
    $allowedServices = [];
    if ($user instanceof OperatoreUser) {
      $allowedServices = $user->getServiziAbilitati()->toArray();
    }

    return $allowedServices;
  }
}
