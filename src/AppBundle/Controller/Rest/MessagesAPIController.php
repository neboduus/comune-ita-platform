<?php

namespace AppBundle\Controller\Rest;


use AppBundle\Dto\Message;
use AppBundle\Entity\Allegato;
use AppBundle\Entity\AllegatoMessaggio;
use AppBundle\Entity\OperatoreUser;
use AppBundle\Entity\Pratica;
use AppBundle\Entity\Message as MessageEntity;
use AppBundle\Security\Voters\ApplicationVoter;
use AppBundle\Security\Voters\MessageVoter;
use AppBundle\Services\InstanceService;
use AppBundle\Services\ModuloPdfBuilderService;
use AppBundle\Services\PraticaStatusService;
use AppBundle\Utils\UploadedBase64File;
use Doctrine\ORM\EntityManagerInterface;
use AppBundle\Model\MetaPagedList;
use AppBundle\Model\LinksPagedList;

use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\QueryBuilder;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use League\Csv\Exception;
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
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class MessagesAPIController
 * @property EntityManagerInterface em
 * @property InstanceService is
 * @package AppBundle\Controller
 *
 */
class MessagesAPIController extends AbstractFOSRestController
{

  /** @var EntityManagerInterface */
  private $em;

  /** @var InstanceService */
  private $is;

  /** @var PraticaStatusService  */
  private $statusService;

  protected $router;

  protected $baseUrl = '';

  /** @var LoggerInterface */
  protected $logger;

  /** @var TranslatorInterface */
  private $translator;

  /**
   * ApplicationsAPIController constructor.
   * @param EntityManagerInterface $em
   * @param InstanceService $is
   * @param PraticaStatusService $statusService
   * @param UrlGeneratorInterface $router
   * @param LoggerInterface $logger
   * @param TranslatorInterface $translator
   */
  public function __construct(
    EntityManagerInterface $em,
    InstanceService $is,
    PraticaStatusService $statusService,
    UrlGeneratorInterface $router,
    LoggerInterface $logger,
    TranslatorInterface $translator
  ) {
    $this->em = $em;
    $this->is = $is;
    $this->statusService = $statusService;
    $this->router = $router;
    $this->baseUrl = $this->router->generate('applications_api_list', [], UrlGeneratorInterface::ABSOLUTE_URL);
    $this->logger = $logger;
    $this->translator = $translator;
  }

  /**
   * Retreive Applications messages
   * @Rest\Get("/applications/{id}/messages", name="application_api_messages_get")
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
   *     response=403,
   *     description="Access denied"
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
   * Retreive Applications messages
   * @Rest\Get("/v2/applications/{id}/messages", name="v2_application_api_messages_get")
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
   *      description="Version of Api, default 1. From version 2 list of messages are paginated"
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
   *     description="Retrieve list of messages for the application",
   *     @SWG\Schema(
   *         type="object",
   *         @SWG\Property(property="meta", type="object", ref=@Model(type=MetaPagedList::class)),
   *         @SWG\Property(property="links", type="object", ref=@Model(type=LinksPagedList::class)),
   *         @SWG\Property(property="data", type="array", @SWG\Items(ref=@Model(type=Message::class, groups={"read"})))
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
   *     description="Applcaitons not found"
   * )
   * @SWG\Tag(name="applications")
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

    $repository = $this->getDoctrine()->getRepository('AppBundle:Pratica');
    /** @var Pratica $result */
    $application = $repository->find($id);
    if ($application === null) {
      return $this->view(["Application not found"], Response::HTTP_NOT_FOUND);
    }
    $this->denyAccessUnlessGranted(ApplicationVoter::VIEW, $result);

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
   * Retreive Application message
   * @Rest\Get("/applications/{id}/messages/{messageId}", name="application_api_message_get")
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
   *     response=403,
   *     description="Access denied"
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

    $this->denyAccessUnlessGranted(MessageVoter::VIEW, $result);

    $message= Message::fromEntity($result, $this->baseUrl.'/'.$result->getId());

    return $this->view($message, Response::HTTP_OK);
  }

  /**
   * Create a Message
   * @Rest\Post("/applications/{id}/messages",name="application_message_api_post")
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
   *
   * @SWG\Response(
   *     response=403,
   *     description="Access denied"
   * )
   *
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

    $this->denyAccessUnlessGranted(ApplicationVoter::VIEW, $application);

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
        $allegato->setDescription(Allegato::DEFAULT_DESCRIPTION);
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
   * Retreive a message applications attachment
   * @Rest\Get("/applications/{id}/messages/{messageId}/attachments/{attachmentId}", name="message_api_attachment_get")
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
   * @param $messageId
   * @param $attachmentId
   * @return View|Response
   */
  public function messageAttachmentAction($messageId, $attachmentId)
  {
    $message = $this->em->getRepository('AppBundle:Message')->find($messageId);
    if ($message === null) {
      return $this->view(["Message not found"], Response::HTTP_NOT_FOUND);
    }

    $this->denyAccessUnlessGranted(MessageVoter::VIEW, $message);

    $repository = $this->em->getRepository('AppBundle:Allegato');
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
