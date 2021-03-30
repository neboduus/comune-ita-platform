<?php

namespace AppBundle\Controller\Rest;


use AppBundle\Dto\Application;
use AppBundle\Entity\AdminUser;
use AppBundle\Entity\AllegatoOperatore;
use AppBundle\Entity\OperatoreUser;
use AppBundle\Entity\Pratica;
use AppBundle\Entity\RispostaOperatore;
use AppBundle\Entity\Servizio;
use AppBundle\Form\Base\AllegatoType;
use AppBundle\Model\PaymentOutcome;
use AppBundle\Model\MetaPagedList;
use AppBundle\Model\LinksPagedList;
use AppBundle\Model\Transition;
use AppBundle\Model\File as FileModel;
use AppBundle\Security\Voters\ApplicationVoter;
use AppBundle\Services\InstanceService;
use AppBundle\Services\Manager\PraticaManager;
use AppBundle\Services\ModuloPdfBuilderService;
use AppBundle\Services\PraticaStatusService;
use AppBundle\Utils\UploadedBase64File;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\QueryBuilder;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use League\Csv\Exception;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Form\FormInterface;
use Nelmio\ApiDocBundle\Annotation\Model;
use Swagger\Annotations as SWG;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use JMS\Serializer\SerializerBuilder;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;

/**
 * Class ServicesAPIController
 * @property EntityManagerInterface em
 * @property InstanceService is
 * @package AppBundle\Controller
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

  const TRANSITION_ACCEPT_INTEGRATION = [
    'action' => 'accept-integration',
    'description' => 'Accept integration',
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

  /** @var PraticaStatusService */
  private $statusService;

  /** @var ModuloPdfBuilderService */
  protected $pdfBuilder;

  protected $router;

  protected $baseUrl = '';

  /** @var LoggerInterface */
  protected $logger;

  /** @var TranslatorInterface */
  private $translator;
  /**
   * @var PraticaManager
   */
  private $praticaManager;

  /**
   * ApplicationsAPIController constructor.
   * @param EntityManagerInterface $em
   * @param InstanceService $is
   * @param PraticaStatusService $statusService
   * @param ModuloPdfBuilderService $pdfBuilder
   * @param UrlGeneratorInterface $router
   * @param LoggerInterface $logger
   * @param TranslatorInterface $translator
   * @param PraticaManager $praticaManager
   */
  public function __construct(
    EntityManagerInterface $em,
    InstanceService $is,
    PraticaStatusService $statusService,
    ModuloPdfBuilderService $pdfBuilder,
    UrlGeneratorInterface $router,
    LoggerInterface $logger,
    TranslatorInterface $translator,
    PraticaManager $praticaManager
  ) {
    $this->em = $em;
    $this->is = $is;
    $this->statusService = $statusService;
    $this->pdfBuilder = $pdfBuilder;
    $this->router = $router;
    $this->baseUrl = $this->router->generate('applications_api_list', [], UrlGeneratorInterface::ABSOLUTE_URL);
    $this->logger = $logger;
    $this->translator = $translator;
    $this->praticaManager = $praticaManager;
  }

  /**
   * List all Applications
   *
   * @Rest\Get("", name="applications_api_list")
   *
   * @SWG\Parameter(
   *      name="Authorization",
   *      in="header",
   *      description="The authentication Bearer",
   *      required=false,
   *      type="string"
   *  )
   * @SWG\Parameter(
   *      name="version",
   *      in="query",
   *      type="string",
   *      required=false,
   *      description="Version of Api, default 1. From version 2 data field keys are exploded in a json objet instead of version 1.* the are flattened strings"
   *  )
   * @SWG\Parameter(
   *      name="service",
   *      in="query",
   *      type="string",
   *      required=false,
   *      description="Slug of the service"
   *  )
   * @SWG\Parameter(
   *      name="order",
   *      in="query",
   *      type="string",
   *      required=false,
   *      description="Order field. Default creationTime"
   *  )
   * @SWG\Parameter(
   *      name="sort",
   *      in="query",
   *      type="string",
   *      required=false,
   *      description="Sorting criteria of the order field. Default ASC"
   *  )
   * @SWG\Parameter(
   *      name="offset",
   *      in="query",
   *      type="integer",
   *      required=false,
   *      description="Offset of the query"
   *  )
   * @SWG\Parameter(
   *      name="limit",
   *      in="query",
   *      type="integer",
   *      required=false,
   *      description="Limit of the query",
   *      maximum="100"
   *  )
   *
   * @SWG\Response(
   *     response=200,
   *     description="Retrieve list of applications",
   *     @SWG\Schema(
   *         type="object",
   *         @SWG\Property(property="meta", type="object", ref=@Model(type=MetaPagedList::class)),
   *         @SWG\Property(property="links", type="object", ref=@Model(type=LinksPagedList::class)),
   *         @SWG\Property(property="data", type="array", @SWG\Items(ref=@Model(type=Application::class, groups={"read"})))
   *     )
   * )
   *
   * @SWG\Response(
   *     response=403,
   *     description="Access denied"
   * )
   *
   * @SWG\Tag(name="applications")
   */

  public function getApplicationsAction(Request $request)
  {
    $this->denyAccessUnlessGranted(['ROLE_OPERATORE','ROLE_ADMIN']);

    $offset = intval($request->get('offset', 0));
    $limit = intval($request->get('limit', 10));
    $version = intval($request->get('version', 1));

    $serviceParameter = $request->get('service', false);
    $orderParameter = $request->get('order', false);
    $sortParameter = $request->get('sort', false);

    if ($limit > 100) {
      return $this->view(["Limit parameter is too high"], Response::HTTP_BAD_REQUEST);
    }

    $repositoryService = $this->em->getRepository('AppBundle:Servizio');
    $allowedServices = $this->getAllowedServices();

    if (empty($allowedServices)) {
      return $this->view(["You are not allowed to view applications"], Response::HTTP_FORBIDDEN);
    }

    $queryParameters = ['offset' => $offset, 'limit' => $limit];
    if ($serviceParameter) {
      $queryParameters['service'] = $serviceParameter;
    }
    if ($orderParameter) {
      $queryParameters['order'] = $orderParameter;
    }
    if ($sortParameter) {
      $queryParameters['sort'] = $sortParameter;
    }

    if ($serviceParameter) {
      $service = $repositoryService->findOneBy(['slug' => $serviceParameter]);
      if (!$service instanceof Servizio) {
        return $this->view(["Service not found"], Response::HTTP_NOT_FOUND);
      }

      if (!in_array($service->getId(), $allowedServices)) {
        return $this->view(["You are not allowed to view applications of passed service"], Response::HTTP_FORBIDDEN);
      }

      $allowedServices = [$service->getId()];
    }

    $repoApplications = $this->em->getRepository(Pratica::class);
    /** @var QueryBuilder $query */
    $query = $repoApplications->createQueryBuilder('a')
      ->select('count(a.id)')
      ->where('a.status != :status')
      ->setParameter('status', Pratica::STATUS_DRAFT);

    $query
      ->andWhere('a.servizio IN (:serviceId)')
      ->setParameter('serviceId', $allowedServices);
    $criteria = ['servizio' => $allowedServices];


    try {
      $count = $query
        ->getQuery()
        ->getSingleScalarResult();
    } catch (NoResultException $e) {
      $count = 0;
    } catch (NonUniqueResultException $e) {
      return $this->view($e->getMessage(), Response::HTTP_I_AM_A_TEAPOT);
    }

    $result = [];
    $result['meta']['count'] = $count;
    $result['meta']['parameter']['offset'] = $offset;
    $result['meta']['parameter']['limit'] = $limit;

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

    $order = $orderParameter ? $orderParameter : "creationTime";
    $sort = $sortParameter ? $sortParameter : "ASC";
    try {
      $statuses = Pratica::getStatuses();
      unset($statuses[Pratica::STATUS_DRAFT]);
      $criteria['status'] = array_keys($statuses);
      $applications = $repoApplications->findBy($criteria, [$order => $sort], $limit, $offset);
      foreach ($applications as $s) {
        $result ['data'][] = Application::fromEntity($s, $this->baseUrl.'/'.$s->getId(), true, $version);
      }

      return $this->view($result, Response::HTTP_OK);
    } catch (\Exception $exception) {
      return $this->view($exception->getMessage(), Response::HTTP_BAD_REQUEST);
    }
  }


  /**
   * Retreive an Applications
   * @Rest\Get("/{id}", name="application_api_get")
   *
   * @SWG\Parameter(
   *      name="version",
   *      in="query",
   *      type="string",
   *      required=false,
   *      description="Version of Api, default 1. From version 2 data field keys are exploded in a json objet instead of version 1.* the are flattened strings"
   *  )
   *
   * @SWG\Response(
   *     response=200,
   *     description="Retreive an Application",
   *     @Model(type=Application::class, groups={"read"})
   * )
   *
   * @SWG\Response(
   *     response=403,
   *     description="Access denied"
   * )
   *
   * @SWG\Response(
   *     response=404,
   *     description="Application not found"
   * )
   * @SWG\Tag(name="applications")
   *
   * @param $id
   * @param Request $request
   * @return View
   */
  public function getApplicationAction($id, Request $request)
  {
    $version = intval($request->get('version', 1));

    try {
      $repository = $this->em->getRepository('AppBundle:Pratica');
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

      $data = Application::fromEntity($result, $this->baseUrl.'/'.$result->getId(), true, $version);

      return $this->view($data, Response::HTTP_OK);
    } catch (\Exception $e) {
      return $this->view(["Identifier conversion error"], Response::HTTP_BAD_REQUEST);
    }
  }

  /**
   * Retreive application history
   * @Rest\Get("/{id}/history", name="application_api_get_history")
   *
   * @SWG\Response(
   *     response=200,
   *     description="Retreive application history",
   *     @SWG\Schema(
   *         type="array",
   *         @SWG\Items(ref=@Model(type=Transition::class))
   *     )
   * )
   *
   * @SWG\Response(
   *     response=403,
   *     description="Access denied"
   * )
   *
   * @SWG\Response(
   *     response=404,
   *     description="Application not found"
   * )
   * @SWG\Tag(name="applications")
   *
   * @param $id
   * @param Request $request
   * @return View
   */
  public function getApplicationHistoryAction($id, Request $request)
  {
    try {
      $repository = $this->em->getRepository('AppBundle:Pratica');
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
   * Retreive an Applications attachment
   * @Rest\Get("/{id}/attachments/{attachmentId}", name="application_api_attachment_get")
   *
   * @SWG\Response(
   *     response=200,
   *     description="Retreive attachment file",
   * )
   *
   * @SWG\Response(
   *     response=403,
   *     description="Access denied"
   * )
   *
   * @SWG\Response(
   *     response=404,
   *     description="Attachment not found"
   * )
   * @SWG\Tag(name="applications")
   *
   * @param $id
   * @return BinaryFileResponse|View
   */
  public function attachmentAction($id, $attachmentId)
  {

    $repository = $this->em->getRepository('AppBundle:Allegato');
    $result = $repository->find($attachmentId);
    if ($result === null) {
      return $this->view(["Attachment not found"], Response::HTTP_NOT_FOUND);
    }
    $pratica = $this->em->getRepository('AppBundle:Pratica')->find($id);

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
      /** @var File $file */
      $file = $result->getFile();
      $fileContent = file_get_contents($file->getPathname());
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
   * Update payment data of an application
   * @Route("/{id}/payment", name="applications_payment_api_post")
   * @Rest\Post("/{id}/payment", name="applications_payment_api_post")
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
   *     name="Payment data",
   *     in="body",
   *     type="json",
   *     description="Update payment data of an application",
   *     required=true,
   *     @SWG\Schema(
   *         type="object",
   *         ref=@Model(type=PaymentOutcome::class)
   *     )
   * )
   *
   * @SWG\Response(
   *     response=200,
   *     description="Updated"
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
   *
   * @SWG\Response(
   *     response=422,
   *     description="Unprocessable Entity"
   * )
   *
   * @SWG\Tag(name="applications")
   *
   * @param Request $request
   * @return View
   */
  public function postApplicationPaymentAction($id, Request $request)
  {
    $repository = $this->em->getRepository('AppBundle:Pratica');
    /** @var Pratica $application */
    $application = $repository->find($id);

    if (!$application) {
      return $this->view("Application not found", Response::HTTP_NOT_FOUND);
    }

    $this->denyAccessUnlessGranted(ApplicationVoter::VIEW, $application);

    if (!in_array(
      $application->getStatus(),
      [Pratica::STATUS_PAYMENT_OUTCOME_PENDING, Pratica::STATUS_PAYMENT_PENDING]
    )) {
      return $this->view("Application isn't in correct state", Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    $paymentOutcome = new paymentOutcome();
    $form = $this->createForm('AppBundle\Form\PaymentOutcomeType', $paymentOutcome);
    $this->processForm($request, $form);

    if ($form->isSubmitted() && !$form->isValid()) {
      $errors = $this->getErrorsFromForm($form);
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

        // Se la pratica ha già un esito significa che è una pratiaca con servizio differito
        if ($application->getEsito()) {
          if ($application->getServizio()->isProtocolRequired()) {
            $this->statusService->setNewStatus($application, Pratica::STATUS_COMPLETE_WAITALLEGATIOPERATORE);
          } else {
            $this->statusService->setNewStatus($application, Pratica::STATUS_COMPLETE);
          }
        } else {
          // Invio la pratica
          $application->setSubmissionTime(time());
          $this->statusService->setNewStatus($application, Pratica::STATUS_PRE_SUBMIT);
        }

      } else {
        $this->statusService->setNewStatus($application, Pratica::STATUS_PAYMENT_ERROR);
      }

    } catch (\Exception $e) {

      $data = [
        'type' => 'error',
        'title' => 'There was an error during save process',
        'description' => $e->getMessage(),
      ];
      $this->logger->error(
        $e->getMessage(),
        ['request' => $request]
      );

      return $this->view($data, Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    return $this->view("Application Payment Modified Successfully", Response::HTTP_OK);
  }

  /**
   * Patch an Application
   * @Rest\Patch("/{id}", name="applications_api_patch")
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
   *     name="Application",
   *     in="body",
   *     type="json",
   *     description="The application to patch",
   *     required=true,
   *     @SWG\Schema(
   *         type="object",
   *         ref=@Model(type=Application::class, groups={"write"})
   *     )
   * )
   *
   * @SWG\Response(
   *     response=200,
   *     description="Patch an Application"
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
   * @SWG\Tag(name="applications")
   *
   * @param $id
   * @param Request $request
   * @return View
   */
  public function patchApplicationAction($id, Request $request)
  {

    $repository = $this->em->getRepository('AppBundle:Pratica');
    $application = $repository->find($id);

    if (!$application) {
      return $this->view("Object not found", Response::HTTP_NOT_FOUND);
    }

    $this->denyAccessUnlessGranted(ApplicationVoter::EDIT, $application);

    if (in_array(
      $application->getStatus(),
      [Pratica::STATUS_DRAFT, Pratica::STATUS_PAYMENT_OUTCOME_PENDING, Pratica::STATUS_PAYMENT_PENDING]
    )) {
      return $this->view("Application isn't in correct state", Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    if ($application->getType() !== Pratica::TYPE_FORMIO) {
      return $this->view("Application can not be protocolled", Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    $_application = Application::fromEntity($application);
    $form = $this->createForm('AppBundle\Form\ApplicationType', $_application);
    $this->processForm($request, $form);

    if (!$form->isValid()) {
      $errors = $this->getErrorsFromForm($form);
      $data = [
        'type' => 'validation_error',
        'title' => 'There was a validation error',
        'errors' => $errors,
      ];

      return $this->view($data, Response::HTTP_BAD_REQUEST);
    }

    try {
      if (!$application->getNumeroProtocollo() && $application->getStatus() == Pratica::STATUS_SUBMITTED && $application->getServizio()->isProtocolRequired()) {
        // Update application status if protocol is enabled and application is submitted
        $this->statusService->setNewStatus($application, Pratica::STATUS_REGISTERED);
      }
      $rispostaOperatore = $application->getRispostaOperatore();
      if ($rispostaOperatore) {
        if (!$rispostaOperatore->getNumeroProtocollo() && $application->getStatus(
          ) == Pratica::STATUS_COMPLETE_WAITALLEGATIOPERATORE) {
          $this->statusService->setNewStatus($application, Pratica::STATUS_COMPLETE);
        }
        if (!$rispostaOperatore->getNumeroProtocollo() && $application->getStatus(
          ) == Pratica::STATUS_CANCELLED_WAITALLEGATIOPERATORE) {
          $this->statusService->setNewStatus($application, Pratica::STATUS_CANCELLED);
        }
      }

      $application = $_application->toEntity($application);
      $this->em->persist($application);
      $this->em->flush();

    } catch (\Exception $e) {

      $data = [
        'type' => 'error',
        'title' => 'There was an error during save process',
      ];
      $this->get('logger')->error(
        $e->getMessage(),
        ['request' => $request]
      );

      return $this->view($data, Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    return $this->view("Object Patched Successfully", Response::HTTP_OK);
  }

  /**
   * Submit an application
   * @Rest\Post("/{id}/transition/submit", name="application_api_post_transition_submit")
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
   *     response=204,
   *     description="Updated"
   * )
   *
   * @SWG\Response(
   *     response=403,
   *     description="Access denied"
   * )
   *
   * @SWG\Response(
   *     response=404,
   *     description="Application not found"
   * )
   * @SWG\Tag(name="applications")
   *
   * @param $id
   * @param Request $request
   * @return View
   */
  public function postApplicationTransitionAction($id, Request $request)
  {
    try {
      $repository = $this->em->getRepository('AppBundle:Pratica');
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
        'description' => $e->getMessage(),
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
   * @SWG\Parameter(
   *     name="Authorization",
   *     in="header",
   *     description="The authentication Bearer",
   *     required=true,
   *     type="string"
   * )
   *
   * @SWG\Parameter(
   *     name="Message",
   *     in="body",
   *     type="json",
   *     description="The transition to create",
   *     required=true,
   *     @SWG\Schema(
   *        type="object",
   *        @SWG\Property(property="protocol_folder_number", type="string", description="Protocol folder number"),
   *        @SWG\Property(property="protocol_folder_code", type="string", description="Protocol folder code"),
   *        @SWG\Property(property="protocol_number", type="string", description="Protocol number"),
   *        @SWG\Property(property="protocol_document_id", type="string", description="Protocol document id")
   *     )
   * )
   *
   * @SWG\Response(
   *     response=204,
   *     description="Updated"
   * )
   *
   * @SWG\Response(
   *     response=403,
   *     description="Access denied"
   * )
   *
   * @SWG\Response(
   *     response=404,
   *     description="Application not found"
   * )
   * @SWG\Tag(name="applications")
   *
   * @param $id
   * @param Request $request
   * @return View
   */
  public function postApplicationTransitionRegisterAction($id, Request $request)
  {
    try {
      $repository = $this->em->getRepository('AppBundle:Pratica');
      $application = $repository->find($id);
      if ($application === null) {
        throw new Exception('Application not found');
      }
      $this->denyAccessUnlessGranted(ApplicationVoter::EDIT, $application);

      if (!empty($application->getNumeroProtocollo())) {
        return $this->view("Application already has a protocol number", Response::HTTP_UNPROCESSABLE_ENTITY);
      }

      if (!$application->getServizio()->isProtocolRequired()) {
        return $this->view("Application does not need to be protocolled", Response::HTTP_UNPROCESSABLE_ENTITY);
      }

      if ($application->getType() !== Pratica::TYPE_FORMIO) {
        return $this->view("Application can not be protocolled", Response::HTTP_UNPROCESSABLE_ENTITY);
      }

      $form = $this->createFormBuilder(null, ['allow_extra_fields' => true,'csrf_protection' => false])
        ->add('protocol_folder_number', TextType::class, [
          'constraints' => [new NotBlank(), new NotNull(),
          ],
        ])
        ->add('protocol_folder_code', TextType::class)
        ->add('protocol_number', TextType::class, [
          'constraints' => [new NotBlank(), new NotNull(),
          ],
        ])
        ->add('protocol_document_id', TextType::class, [
          'constraints' => [new NotBlank(), new NotNull(),
          ],
        ])
        ->getForm();
      $this->processForm($request, $form);
      if ($form->isSubmitted() && !$form->isValid()) {
        $errors = $this->getErrorsFromForm($form);
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
        'description' => $e->getMessage(),
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
   * @SWG\Parameter(
   *     name="Authorization",
   *     in="header",
   *     description="The authentication Bearer",
   *     required=true,
   *     type="string"
   * )
   *
   * @SWG\Response(
   *     response=204,
   *     description="Updated"
   * )
   *
   * @SWG\Response(
   *     response=403,
   *     description="Access denied"
   * )
   *
   * @SWG\Response(
   *     response=404,
   *     description="Application not found"
   * )
   * @SWG\Tag(name="applications")
   *
   * @param $id
   * @param Request $request
   * @return View
   */
  public function postApplicationTransitionAssignAction($id, Request $request)
  {
    try {
      $repository = $this->em->getRepository('AppBundle:Pratica');
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
        'description' => $e->getMessage(),
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
   * @SWG\Parameter(
   *     name="Authorization",
   *     in="header",
   *     description="The authentication Bearer",
   *     required=true,
   *     type="string"
   * )
   *
   * @SWG\Parameter(
   *     name="Message",
   *     in="body",
   *     type="json",
   *     description="The transition to create",
   *     required=true,
   *     @SWG\Schema(
   *        type="object",
   *        @SWG\Property(property="message", type="string", description="Application outcome"),
   *        @SWG\Property(property="attachments", type="array", @SWG\Items(ref=@Model(type=FileModel::class)))
   *     )
   * )
   *
   *
   * @SWG\Response(
   *     response=204,
   *     description="Updated"
   * )
   *
   * @SWG\Response(
   *     response=403,
   *     description="Access denied"
   * )
   *
   * @SWG\Response(
   *     response=404,
   *     description="Application not found"
   * )
   * @SWG\Tag(name="applications")
   *
   * @param $id
   * @param Request $request
   * @return View
   */
  public function postApplicationTransitionOutcomeAction($id, Request $request)
  {
    try {
      $repository = $this->em->getRepository('AppBundle:Pratica');
      $application = $repository->find($id);
      if ($application === null) {
        throw new Exception('Application not found');
      }
      $this->denyAccessUnlessGranted(ApplicationVoter::ACCEPT_OR_REJECT, $application);

      $defaultData = [
        'message' => null,
        'attachments' => null,
      ];
      $form = $this->createForm('AppBundle\Form\Rest\Transition\OutcomeFormType', $defaultData);
      $this->processForm($request, $form);
      if ($form->isSubmitted() && !$form->isValid()) {
        $errors = $this->getErrorsFromForm($form);
        $data = [
          'type' => 'validation_error',
          'title' => 'There was a validation error',
          'errors' => $errors,
        ];

        return $this->view($data, Response::HTTP_BAD_REQUEST);
      }

      $data = $form->getData();
      $application->setEsito($request->get('_route') == 'application_api_post_transition_accept');
      $application->setMotivazioneEsito($data['message']);

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
   * @SWG\Parameter(
   *     name="Authorization",
   *     in="header",
   *     description="The authentication Bearer",
   *     required=true,
   *     type="string"
   * )
   *
   * @SWG\Response(
   *     response=204,
   *     description="Updated"
   * )
   *
   * @SWG\Response(
   *     response=403,
   *     description="Access denied"
   * )
   *
   * @SWG\Response(
   *     response=404,
   *     description="Application not found"
   * )
   * @SWG\Tag(name="applications")
   *
   * @param $id
   * @param Request $request
   * @return View
   */
  public function postApplicationTransitionWithDrawAction($id, Request $request)
  {
    try {
      $repository = $this->em->getRepository('AppBundle:Pratica');
      $application = $repository->find($id);
      if ($application === null) {
        throw new Exception('Application not found');
      }

      $this->denyAccessUnlessGranted(ApplicationVoter::WITHDRAW, $application);

      if ($application->getUser()->getId() != $this->getUser()->getId()) {
        throw new Exception('You are not allowed to operate on this application');
      }
      $this->statusService->setNewStatus($application, Pratica::STATUS_WITHDRAW);
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

  /**
   * @return array
   */
  private function getAllowedServices(): array
  {
    $user = $this->getUser();
    $repositoryService = $this->em->getRepository('AppBundle:Servizio');
    $allowedServices = [];
    if ($user instanceof OperatoreUser) {
      $allowedServices = $user->getServiziAbilitati()->toArray();
    } elseif ($user instanceof AdminUser) {
      $services = $repositoryService->findAll();
      /** @var Servizio $service */
      foreach ($services as $service) {
        $allowedServices []= $service->getId();
      }
    }
    return $allowedServices;
  }
}
