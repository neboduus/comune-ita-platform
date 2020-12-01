<?php

namespace AppBundle\Controller\Rest;


use AppBundle\Dto\Application;
use AppBundle\Dto\Service;
use AppBundle\Dto\Message;
use AppBundle\Entity\Allegato;
use AppBundle\Entity\AllegatoMessaggio;
use AppBundle\Entity\AllegatoOperatore;
use AppBundle\Entity\Pratica;
use AppBundle\Entity\Message as MessageEntity;
use AppBundle\Entity\RispostaOperatore;
use AppBundle\Entity\Servizio;
use AppBundle\Form\Base\AllegatoType;
use AppBundle\Model\PaymentOutcome;
use AppBundle\Model\MetaPagedList;
use AppBundle\Model\LinksPagedList;
use AppBundle\Services\InstanceService;
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
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
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

/**
 * Class ServicesAPIController
 * @property EntityManagerInterface em
 * @property InstanceService is
 * @package AppBundle\Controller
 * @Route("/applications")
 */
class ApplicationsAPIController extends AbstractFOSRestController
{

  /**
   * @var
   */
  private $statusService;

  /**
   * @var ModuloPdfBuilderService
   */
  protected $pdfBuilder;

  protected $router;

  protected $baseUrl = '';

  /** @var LoggerInterface */
  protected $logger;

  public function __construct(
    EntityManagerInterface $em,
    InstanceService $is,
    PraticaStatusService $statusService,
    ModuloPdfBuilderService $pdfBuilder,
    UrlGeneratorInterface $router,
    LoggerInterface $logger
  ) {
    $this->em = $em;
    $this->is = $is;
    $this->statusService = $statusService;
    $this->pdfBuilder = $pdfBuilder;
    $this->router = $router;
    $this->baseUrl = $this->router->generate('applications_api_list', [], UrlGeneratorInterface::ABSOLUTE_URL);
    $this->logger = $logger;
  }

  /**
   * List all Applications
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
   * @SWG\Tag(name="applications")
   */

  public function getApplicationsAction(Request $request)
  {
    $offset = intval($request->get('offset', 0));
    $limit = intval($request->get('limit', 10));
    $version = intval($request->get('version', 1));

    $serviceParameter = $request->get('service', false);
    $orderParameter = $request->get('order', false);
    $sortParameter = $request->get('sort', false);

    if ($limit > 100) {
      return $this->view(["Limit parameter is too high"], Response::HTTP_BAD_REQUEST);
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

    $repositoryService = $this->em->getRepository('AppBundle:Servizio');
    $service = $repositoryService->findOneBy(['slug' => $serviceParameter]);

    if ($serviceParameter && !$service) {
      return $this->view(["Service not found"], Response::HTTP_NOT_FOUND);
    }

    $repoApplications = $this->em->getRepository(Pratica::class);
    /** @var QueryBuilder $query */
    $query = $repoApplications->createQueryBuilder('a')
      ->select('count(a.id)')
      ->where('a.status != :status')
      ->setParameter('status', Pratica::STATUS_DRAFT);

    $criteria = [];
    if ($service instanceof Servizio) {
      $query
        ->andWhere('a.servizio = :serviceId')
        ->setParameter('serviceId', $service->getId());

      $criteria = ['servizio' => $service->getId()];
    }

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
      $result = $repository->find($id);
      if ($result === null) {
        return $this->view(["Application not found"], Response::HTTP_NOT_FOUND);
      }
      $data = Application::fromEntity($result, $this->baseUrl.'/'.$result->getId(), true, $version);

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
    $application = $repository->find($id);

    if (!$application) {
      return $this->view("Application not found", Response::HTTP_NOT_FOUND);
    }

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

        // Invio la pratica
        $application->setSubmissionTime(time());
        $this->statusService->setNewStatus($application, Pratica::STATUS_PRE_SUBMIT);

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

    if (in_array(
      $application->getStatus(),
      [Pratica::STATUS_DRAFT, Pratica::STATUS_PAYMENT_OUTCOME_PENDING, Pratica::STATUS_PAYMENT_PENDING]
    )) {
      return $this->view("Application isn't in correct state", Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    if (!$application->getServizio()->isProtocolRequired()) {
      return $this->view("Application does not need to be protocolled", Response::HTTP_UNPROCESSABLE_ENTITY);
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
      if (!$application->getNumeroProtocollo() && $application->getStatus() == Pratica::STATUS_SUBMITTED) {
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
   * Retreive Applications messages
   * @Rest\Get("/{id}/messages", name="application_api_messages_get")
   *
   * @SWG\Response(
   *     response=200,
   *     description="Retrieve list of messages for the application",
   *     @SWG\Schema(
   *         type="array",
   *         @SWG\Items(ref=@Model(type=Message::class, groups={"read"}))
   *     )
   * )
   *
   * @SWG\Response(
   *     response=404,
   *     description="Applcaitons not found"
   * )
   * @SWG\Tag(name="applications")
   *
   * @param $id
   * @return View
   */
  public function messagesAction($id)
  {

    $repository = $this->getDoctrine()->getRepository('AppBundle:Pratica');
    /** @var Pratica $result */
    $result = $repository->find($id);
    if ($result === null) {
      return $this->view(["Application not found"], Response::HTTP_NOT_FOUND);
    }

    $messages = [];
    /** @var MessageEntity $m */
    foreach ($result->getMessages() as $m) {
      if ($m->getVisibility() != MessageEntity::VISIBILITY_INTERNAL) {
        $messages [] = Message::fromEntity($m, $this->baseUrl.'/'.$result->getId());
      }
    }

    return $this->view($messages, Response::HTTP_OK);
  }


  /**
   * Retreive Application message
   * @Rest\Get("/{id}/messages/{messageId}", name="application_api_message_get")
   *
   * @SWG\Response(
   *     response=200,
   *     description="Retrieve a message of the application",
   *     @SWG\Schema(
   *         type="array",
   *         @SWG\Items(ref=@Model(type=Message::class, groups={"read"}))
   *     )
   * )
   *
   * @SWG\Response(
   *     response=404,
   *     description="Message not found"
   * )
   * @SWG\Tag(name="applications")
   *
   * @param $messageId
   * @return View
   */
  public function messageAction($messageId)
  {

    $repository = $this->getDoctrine()->getRepository('AppBundle:Message');
    /** @var MessageEntity $result */
    $result = $repository->find($messageId);
    if ($result === null) {
      return $this->view(["Message not found"], Response::HTTP_NOT_FOUND);
    }

    $message [] = Message::fromEntity($result, $this->baseUrl.'/'.$result->getId());

    return $this->view($message, Response::HTTP_OK);
  }

  /**
   * Create a Message
   * @Rest\Post("/{id}/messages",name="application_message_api_post")
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
   *     description="The message to create",
   *     required=true,
   *     @SWG\Schema(
   *         type="object",
   *         ref=@Model(type=Message::class, groups={"write"})
   *     )
   * )
   *
   * @SWG\Response(
   *     response=201,
   *     description="Message created"
   * )
   *
   * @SWG\Response(
   *     response=400,
   *     description="Bad request"
   * )
   * @SWG\Tag(name="applications")
   *
   * @param $id
   * @param Request $request
   * @return View
   */
  public function postMessageAction($id, Request $request)
  {

    $repository = $this->getDoctrine()->getRepository('AppBundle:Pratica');

    /** @var Pratica $application */
    $application = $repository->find($id);
    if ($application === null) {
      return $this->view(["Application not found"], Response::HTTP_NOT_FOUND);
    }

    $message = new Message();
    $message->setApplication($application);
    $user = $this->getUser();
    $message->setAuthor($user);
    $message->setCreatedAt(new \DateTime());

    $form = $this->createForm('AppBundle\Form\Rest\MessageFormType', $message);
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
      $messageEntity = $message->toEntity();
      foreach ($message->getAttachments() as $attachment) {
        $base64Content = $attachment->getFile();
        $file = new UploadedBase64File($base64Content, $attachment->getMimeType());
        $allegato = new AllegatoMessaggio();
        $allegato->addMessage($messageEntity);
        $allegato->setFile($file);
        $allegato->setOwner($application->getUser());
        $allegato->setDescription('Allegato senza descrizione');
        $allegato->setOriginalFilename($attachment->getName());
        $this->em->persist($allegato);
        $messageEntity->addAttachment($allegato);
      }

      $this->em->persist($messageEntity);
      $this->em->flush();
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

    return $this->view(
      Message::fromEntity($messageEntity, $this->baseUrl.'/'.$messageEntity->getId()),
      Response::HTTP_CREATED
    );
  }


  /**
   * Patch an application message
   * @Rest\Patch("/{id}/messages/{messageId}",name="application_message_api_patch")
   *
   * @SWG\Parameter(
   *     name="Authorization",
   *     in="header",
   *     description="The authentication Bearer",
   *     required=true,
   *     type="string"
   * )
   *
   *
   * @SWG\Parameter(
   *     name="Message",
   *     in="body",
   *     type="json",
   *     description="The message to create",
   *     required=true,
   *     @SWG\Schema(
   *         type="object",
   *         ref=@Model(type=Message::class, groups={"write"})
   *     )
   * )
   *
   * @SWG\Response(
   *     response=200,
   *     description="Patch a message"
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
   * @SWG\Tag(name="applications")
   *
   * @param $id
   * @param $messageId
   * @param Request $request
   * @return View
   */
  public function patchMessageAction($id, $messageId, Request $request)
  {

    $allowedPatchFields = ['sent_at', 'read_at', 'clicked_at', 'protocolled_at', 'protocol_number'];

    $repository = $this->getDoctrine()->getRepository('AppBundle:Message');
    $messageEntity = $repository->find($messageId);
    if (!$messageEntity) {
      return $this->view("Message not found", Response::HTTP_NOT_FOUND);
    }

    $user = $this->getUser();
    if ($messageEntity->getAuthor() != $user) {
      return $this->view("You can't update messages of other users", Response::HTTP_FORBIDDEN);
    }

    if ($messageEntity->getProtocolNumber() != null) {
      return $this->view("Message has been protocolled, you can't update it!", Response::HTTP_FORBIDDEN);
    }

    foreach ($request->request->all() as $k => $item) {
      if (!in_array($k, $allowedPatchFields)) {
        $request->request->remove($k);
      }
    }

    $message = Message::fromEntity($messageEntity, $this->baseUrl.'/'.$messageEntity->getId());

    $form = $this->createForm('AppBundle\Form\Rest\MessageFormType', $message);
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

    $messageEntity = $message->toEntity($messageEntity);
    try {
      $this->em->persist($messageEntity);
      $this->em->flush();
    } catch (\Exception $e) {

      $data = [
        'type' => 'error',
        'title' => 'There was an error during save process',
      ];
      $this->logger->error(
        $e->getMessage(),
        ['request' => $request]
      );

      return $this->view($data, Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    return $this->view("Message Patched Successfully", Response::HTTP_OK);
  }


  /**
   * Retreive a message applications attachment
   * @Rest\Get("/{id}/messages/{messageId}/attachments/{attachmentId}", name="message_api_attachment_get")
   *
   * @SWG\Response(
   *     response=200,
   *     description="Retreive attachment file",
   * )
   *
   * @SWG\Response(
   *     response=404,
   *     description="Attachment not found"
   * )
   * @SWG\Tag(name="applications")
   *
   * @param $attachmentId
   * @return View|Response
   */
  public function messageAttachmentAction($attachmentId)
  {

    $repository = $this->getDoctrine()->getRepository('AppBundle:Allegato');
    $result = $repository->find($attachmentId);
    if ($result === null) {
      return $this->view(["Attachment not found"], Response::HTTP_NOT_FOUND);
    }

    /** @var File $file */
    $file = $result->getFile();
    $fileContent = file_get_contents($file->getPathname());
    $filename = mb_convert_encoding($result->getFilename(), "ASCII", "auto");
    $response = new Response($fileContent);
    $disposition = $response->headers->makeDisposition(
      ResponseHeaderBag::DISPOSITION_ATTACHMENT,
      $filename
    );

    $response->headers->set('Content-Disposition', $disposition);

    return $response;
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
}
