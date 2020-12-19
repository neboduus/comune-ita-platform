<?php

namespace App\Controller\Rest;


use App\Dto\Message;
use App\Entity\AllegatoMessaggio;
use App\Entity\Pratica;
use App\Entity\Message as MessageEntity;
use App\Services\InstanceService;
use App\Services\ModuloPdfBuilderService;
use App\Services\PraticaStatusService;
use App\Utils\UploadedBase64File;
use Doctrine\ORM\EntityManagerInterface;

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
 * @Route("/applications")
 */
class MessagesAPIController extends AbstractFOSRestController
{

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
