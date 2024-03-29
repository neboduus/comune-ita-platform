<?php

namespace App\Controller\Rest;


use App\Dto\Message;
use App\Entity\Allegato;
use App\Entity\AllegatoMessaggio;
use App\Entity\OperatoreUser;
use App\Entity\Pratica;
use App\Entity\Message as MessageEntity;
use App\Security\Voters\ApplicationVoter;
use App\Security\Voters\MessageVoter;
use App\Services\FileService\AllegatoFileService;
use App\Services\InstanceService;
use App\Services\Manager\MessageManager;
use App\Utils\FormUtils;
use App\Utils\UploadedBase64File;
use Doctrine\ORM\EntityManagerInterface;
use App\Model\MetaPagedList;
use App\Model\LinksPagedList;

use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\QueryBuilder;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use League\Flysystem\FileNotFoundException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Form\FormInterface;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use OpenApi\Annotations as OA;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Class MessagesAPIController
 * @property EntityManagerInterface em
 * @property InstanceService is
 * @package App\Controller
 *
 */
class MessagesAPIController extends AbstractFOSRestController
{

  /** @var EntityManagerInterface */
  private $em;

  /**@var UrlGeneratorInterface */
  protected $router;

  protected $baseUrl = '';

  /** @var LoggerInterface */
  protected $logger;

  /** @var AllegatoFileService */
  private $fileService;
  /**
   * @var MessageManager
   */
  private $messageManager;

  /**
   * ApplicationsAPIController constructor.
   * @param EntityManagerInterface $em
   * @param UrlGeneratorInterface $router
   * @param LoggerInterface $logger
   * @param AllegatoFileService $fileService
   * @param MessageManager $messageManager
   */
  public function __construct(
    EntityManagerInterface $em,
    UrlGeneratorInterface $router,
    LoggerInterface $logger,
    AllegatoFileService $fileService,
    MessageManager $messageManager
  ) {
    $this->em = $em;
    $this->router = $router;
    $this->baseUrl = $this->router->generate('applications_api_list', [], UrlGeneratorInterface::ABSOLUTE_URL);
    $this->logger = $logger;
    $this->fileService = $fileService;
    $this->messageManager = $messageManager;
  }

  /**
   * Retrieve Applications messages
   * @Rest\Get("/applications/{id}/messages", name="application_api_messages_get")
   *
   * @OA\Response(
   *     response=200,
   *     description="Retrieve list of messages for the application",
   *     @OA\JsonContent(
   *         type="array",
   *         @OA\Items(ref=@Model(type=Message::class, groups={"read"}))
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
   *     description="Applcaitons not found"
   * )
   * @OA\Tag(name="applications")
   *
   * @param $id
   * @return View
   */
  public function messagesAction($id)
  {

    $repository = $this->getDoctrine()->getRepository('App\Entity\Pratica');
    /** @var Pratica $result */
    $result = $repository->find($id);
    if ($result === null) {
      return $this->view(["Application not found"], Response::HTTP_NOT_FOUND);
    }

    $this->denyAccessUnlessGranted(ApplicationVoter::VIEW, $result);

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
   * Retrieve Applications messages
   * @Rest\Get("/v2/applications/{id}/messages", name="v2_application_api_messages_get")
   *
   * @OA\Parameter(
   *      name="Authorization",
   *      in="header",
   *      description="The authentication Bearer",
   *      required=false
   *  )
   * @OA\Parameter(
   *      name="version",
   *      in="query",
   *      @OA\Schema(
   *          type="string"
   *      ),
   *      required=false,
   *      description="Version of Api, default 1. From version 2 list of messages are paginated"
   *  )
   * @OA\Parameter(
   *      name="offset",
   *      in="query",
   *      @OA\Schema(
   *          type="string"
   *      ),
   *      required=false,
   *      description="Offset of the query"
   *  )
   * @OA\Parameter(
   *      name="limit",
   *      in="query",
   *      @OA\Schema(
   *          type="string"
   *      ),
   *      required=false,
   *      description="Limit of the query"
   *  )
   *
   * @OA\Response(
   *     response=200,
   *     description="Retrieve list of messages for the application",
   *     @OA\JsonContent(
   *         type="object",
   *         @OA\Property(property="meta", type="object", ref=@Model(type=MetaPagedList::class)),
   *         @OA\Property(property="links", type="object", ref=@Model(type=LinksPagedList::class)),
   *         @OA\Property(property="data", type="array", @OA\Items(ref=@Model(type=Message::class, groups={"read"})))
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
   *     description="Applcaitons not found"
   * )
   * @OA\Tag(name="applications")
   *
   * @param $id
   * @param Request $request
   * @return View
   */
  public function messagesActionV2($id, Request $request)
  {

    $offset = intval($request->get('offset', 0));
    $limit = intval($request->get('limit', 10));

    if ($limit > 100) {
      return $this->view(["Limit parameter is too high"], Response::HTTP_BAD_REQUEST);
    }

    $repository = $this->getDoctrine()->getRepository('App\Entity\Pratica');
    /** @var Pratica $result */
    $application = $repository->find($id);
    if ($application === null) {
      return $this->view(["Application not found"], Response::HTTP_NOT_FOUND);
    }
    $this->denyAccessUnlessGranted(ApplicationVoter::VIEW, $application);

    $queryParameters = ['offset' => $offset, 'limit' => $limit];

    $repoMessages = $this->em->getRepository(MessageEntity::class);
    /** @var QueryBuilder $query */
    $query = $repoMessages->createQueryBuilder('m')
      ->select('count(m.id)')
      ->where('m.application = :application')
      ->setParameter('application', $application);

    $criteria = ['application' => $application];

    try {
      $count = $query
        ->getQuery()
        ->getSingleScalarResult();
    } catch (NoResultException $e) {
      $count = 0;
    } catch (NonUniqueResultException $e) {
      return $this->view($e->getMessage(), Response::HTTP_I_AM_A_TEAPOT);
    }

    if ( $count == 0 ) {
      return $this->view(["Messages not found"], Response::HTTP_NOT_FOUND);
    }

    $parameters = array_merge(['id' => $id], $queryParameters);

    $result = [];
    $result['meta']['count'] = $count;
    $result['meta']['parameter']['offset'] = $offset;
    $result['meta']['parameter']['limit'] = $limit;

    $result['links']['self'] = $this->generateUrl(
      'v2_application_api_messages_get_v2',
      $parameters,
      UrlGeneratorInterface::ABSOLUTE_URL
    );
    $result['links']['prev'] = null;
    $result['links']['next'] = null;
    $result ['data'] = [];

    if ($offset != 0) {
      $parameters['offset'] = $offset - $limit;
      $result['links']['prev'] = $this->generateUrl(
        'v2_application_api_messages_get_v2',
        $parameters,
        UrlGeneratorInterface::ABSOLUTE_URL
      );
    }

    if ($offset + $limit < $count) {
      $parameters['offset'] = $offset + $limit;
      $result['links']['next'] = $this->generateUrl(
        'v2_application_api_messages_get_v2',
        $parameters,
        UrlGeneratorInterface::ABSOLUTE_URL
      );
    }

    $order = "createdAt";
    $sort = "ASC";
    try {
      $messages = $repoMessages->findBy($criteria, [$order => $sort], $limit, $offset);
      foreach ($messages as $m) {
        $result ['data'][] = Message::fromEntity($m, $this->baseUrl.'/'.$application->getId());
      }

      return $this->view($result, Response::HTTP_OK);
    } catch (\Exception $exception) {
      $this->logger->error(
        $exception->getMessage(),
        ['request' => $request]
      );
      return $this->view($exception->getMessage(), Response::HTTP_BAD_REQUEST);
    }
  }


  /**
   * Retrieve Application message
   * @Rest\Get("/applications/{id}/messages/{messageId}", name="application_api_message_get")
   *
   * @OA\Response(
   *     response=200,
   *     description="Retrieve a message of the application",
   *     @OA\JsonContent(
   *         type="array",
   *         @OA\Items(ref=@Model(type=Message::class, groups={"read"}))
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
   *     description="Message not found"
   * )
   * @OA\Tag(name="applications")
   *
   * @param $messageId
   * @return View
   */
  public function messageAction($messageId)
  {

    $repository = $this->getDoctrine()->getRepository('App\Entity\Message');
    /** @var MessageEntity $result */
    $result = $repository->find($messageId);
    if ($result === null) {
      return $this->view(["Message not found"], Response::HTTP_NOT_FOUND);
    }

    $this->denyAccessUnlessGranted(MessageVoter::VIEW, $result);

    $message= Message::fromEntity($result, $this->baseUrl.'/'.$result->getId());

    return $this->view($message, Response::HTTP_OK);
  }

  /**
   * Create a Message
   * @Rest\Post("/applications/{id}/messages",name="application_message_api_post")
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
   *             ref=@Model(type=Message::class, groups={"write"})
   *         )
   *     )
   * )
   *
   * @OA\Response(
   *     response=201,
   *     description="Message created"
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
   * @param $id
   * @param Request $request
   * @return View
   */
  public function postMessageAction($id, Request $request)
  {

    $repository = $this->getDoctrine()->getRepository('App\Entity\Pratica');

    /** @var Pratica $application */
    $application = $repository->find($id);
    if ($application === null) {
      return $this->view(["Application not found"], Response::HTTP_NOT_FOUND);
    }

    $this->denyAccessUnlessGranted(ApplicationVoter::VIEW, $application);

    $message = new Message();
    $message->setApplication($application);
    $user = $this->getUser();
    $message->setAuthor($user);
    $message->setCreatedAt(new \DateTime());

    $form = $this->createForm('App\Form\Rest\MessageFormType', $message);
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
      $messageEntity = $message->toEntity();
      foreach ($message->getAttachments() as $attachment) {
        $base64Content = $attachment->getFile();
        $file = new UploadedBase64File($base64Content, $attachment->getMimeType());
        $allegato = new AllegatoMessaggio();
        $allegato->addMessage($messageEntity);
        $allegato->setFile($file);
        $allegato->setOwner($application->getUser());
        $allegato->setDescription(Allegato::DEFAULT_DESCRIPTION);
        $allegato->setOriginalFilename($attachment->getName());
        $this->em->persist($allegato);
        $messageEntity->addAttachment($allegato);
      }

      $this->messageManager->save($messageEntity);

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

    return $this->view(
      Message::fromEntity($messageEntity, $this->baseUrl.'/'.$messageEntity->getId()),
      Response::HTTP_CREATED
    );
  }


  /**
   * Patch an application message
   * @Rest\Patch("/applications/{id}/messages/{messageId}",name="application_message_api_patch")
   *
   * @Security(name="Bearer")
   *
   *
   * @OA\RequestBody(
   *     description="The message to create",
   *     required=true,
   *     @OA\MediaType(
   *         mediaType="application/json",
   *         @OA\Schema(
   *             type="object",
   *             ref=@Model(type=Message::class, groups={"write"})
   *         )
   *     )
   * )
   *
   * @OA\Response(
   *     response=200,
   *     description="Patch a message"
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
   * @param $messageId
   * @param Request $request
   * @return View
   */
  public function patchMessageAction($id, $messageId, Request $request)
  {

    $allowedPatchFields = ['sent_at', 'read_at', 'clicked_at', 'protocolled_at', 'protocol_number'];

    $repository = $this->getDoctrine()->getRepository('App\Entity\Message');
    $messageEntity = $repository->find($messageId);
    if (!$messageEntity) {
      return $this->view(["Message not found"], Response::HTTP_NOT_FOUND);
    }

    $this->denyAccessUnlessGranted(MessageVoter::EDIT, $messageEntity);

    $user = $this->getUser();

    if ($user instanceof OperatoreUser) {
      /** @var  OperatoreUser $user */

      $enabledServices = $user->getServiziAbilitati();
      $serviceId = $messageEntity->getApplication()->getServizio()->getId();

      if (!$enabledServices->contains($serviceId)) {
        return $this->view(["You can't update messages of this service"], Response::HTTP_FORBIDDEN);
      }
    }

    if ($messageEntity->getProtocolNumber() != null) {
      return $this->view(["Message has been protocolled, you can't update it!"], Response::HTTP_FORBIDDEN);
    }

    foreach ($request->request->all() as $k => $item) {
      if (!in_array($k, $allowedPatchFields)) {
        $request->request->remove($k);
      }
    }

    $message = Message::fromEntity($messageEntity, $this->baseUrl.'/'.$messageEntity->getId());

    $form = $this->createForm('App\Form\Rest\MessageFormType', $message);
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

    $messageEntity = $message->toEntity($messageEntity);
    try {
      $this->em->persist($messageEntity);
      $this->em->flush();
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

    return $this->view(["Message Patched Successfully"], Response::HTTP_OK);
  }


  /**
   * Retrieve a message applications attachment
   * @Rest\Get("/applications/{id}/messages/{messageId}/attachments/{attachmentId}", name="message_api_attachment_get")
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
   * @OA\Tag(name="applications")
   *
   * @param $messageId
   * @param $attachmentId
   * @return View|Response
   */
  public function messageAttachmentAction($messageId, $attachmentId)
  {
    $message = $this->em->getRepository('App\Entity\Message')->find($messageId);
    if ($message === null) {
      return $this->view(["Message not found"], Response::HTTP_NOT_FOUND);
    }

    $this->denyAccessUnlessGranted(MessageVoter::VIEW, $message);

    $repository = $this->em->getRepository('App\Entity\Allegato');
    $result = $repository->find($attachmentId);
    if ($result === null) {
      return $this->view(["Attachment not found"], Response::HTTP_NOT_FOUND);
    }

    /** @var File $file */
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
}
