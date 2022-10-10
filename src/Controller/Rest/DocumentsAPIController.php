<?php

namespace App\Controller\Rest;

use App\Entity\AdminUser;
use App\Entity\CPSUser;
use App\Entity\Document;
use App\Entity\OperatoreUser;
use App\Security\Voters\DocumentVoter;
use App\Services\InstanceService;
use App\Services\Manager\DocumentManager;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManager;
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
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class DocumentsAPIController
 * @property EntityManagerInterface em
 * @property InstanceService is
 * @package App\Controller
 * @Route("/documents")
 */
class DocumentsAPIController extends AbstractFOSRestController
{
  const CURRENT_API_VERSION = '1.0';

  private $em;
  private $is;
  private $rootDir;
  /**
   * @var TranslatorInterface $translator
   */
  private $translator;

  /** @var LoggerInterface */
  private $logger;

  public function __construct(TranslatorInterface $translator, $rootDir, EntityManagerInterface $em, InstanceService $is, LoggerInterface $logger, DocumentManager $documentManager)
  {
    $this->translator = $translator;
    $this->rootDir = $rootDir;
    $this->em = $em;
    $this->is = $is;
    $this->logger = $logger;
    $this->documentManager = $documentManager;
  }

  /**
   * List all Documents
   * @Rest\Get("", name="documents_api_list")
   *
   * @Security(name="Bearer")
   *
   * @OA\Parameter(
   *     name="cf",
   *     in="query",
   *      @OA\Schema(
   *          type="string"
   *      ),
   *     description="Fiscal code of the document's owner"
   * )
   * @OA\Parameter(
   *     name="title",
   *     in="query",
   *      @OA\Schema(
   *          type="string"
   *      ),
   *     description="Document's title"
   * )
   * @OA\Parameter(
   *     name="folder-title",
   *     in="query",
   *      @OA\Schema(
   *          type="string"
   *      ),
   *     description="Document's folder title"
   * )
   * @OA\Parameter(
   *     name="folder",
   *     in="query",
   *      @OA\Schema(
   *          type="string"
   *      ),
   *     description="Document's folder id"
   * )
   *
   * @OA\Response(
   *     response=200,
   *     description="Retrieve list of documents",
   *     @OA\JsonContent(
   *         type="array",
   *         @OA\Items(ref=@Model(type=Document::class, groups={"read"}))
   *     )
   * )
   *
   * @OA\Response(
   *     response=403,
   *     description="Access denied"
   * )
   *
   * @OA\Tag(name="documents")
   * @param Request $request
   * @return View
   */
  public function getDocumentsAction(Request $request)
  {
    $this->denyAccessUnlessGranted(['ROLE_OPERATORE','ROLE_ADMIN']);

    $cf = $request->query->get('cf');
    $title = $request->query->get('title');
    $folderTitle = $request->query->get('folder-title');
    $folder = $request->query->get('folder');

    $qb = $this->em->createQueryBuilder()
      ->select('document')
      ->from('App:Document', 'document')
      ->leftJoin('document.folder', 'folder')
      ->leftJoin('document.owner', 'owner');

    if (isset($cf)) {
      $qb->andWhere('lower(owner.codiceFiscale) = :cf')
        ->setParameter('cf', strtolower($cf));
    }

    if (isset($title)) {
      $qb->andWhere('lower(document.title) = :title')
        ->setParameter('title', strtolower($title));
    }

    if (isset($folderTitle)) {
      $qb->andWhere('lower(folder.title) = :folder-title')
        ->setParameter('folder-title', strtolower($folderTitle));
    }
    if (isset($folder)) {
      $qb->andWhere('folder.id = :folder')
        ->setParameter('folder', $folder);
    }
    $documents = $qb
      ->getQuery()
      ->getResult();

    return $this->view($documents, Response::HTTP_OK);
  }

  /**
   * Retreive a Document
   * @Rest\Get("/{id}", name="document_api_get")
   *
   * @Security(name="Bearer")
   *
   * @OA\Response(
   *     response=200,
   *     description="Retreive a Document",
   *     @Model(type=Document::class, groups={"read"})
   * )
   *
   * @OA\Response(
   *     response=403,
   *     description="Access denied"
   * )
   *
   * @OA\Response(
   *     response=404,
   *     description="Document not found"
   * )
   * @OA\Tag(name="documents")
   *
   * @param $id
   * @return View
   */
  public function getDocumentAction($id)
  {
    try {
      $repository = $this->getDoctrine()->getRepository('App\Entity\Document');
      $document = $repository->find($id);
      if ($document === null) {
        return $this->view(["Object not found"], Response::HTTP_NOT_FOUND);
      }

      $this->denyAccessUnlessGranted(DocumentVoter::VIEW, $document);

      return $this->view($document, Response::HTTP_OK);
    } catch (\Exception $e) {
      return $this->view(["Object not found"], Response::HTTP_NOT_FOUND);
    }
  }

  /**
   * Create a Document
   * @Rest\Post(name="documents_api_post")
   *
   * @Security(name="Bearer")
   *
   * @OA\RequestBody(
   *     description="The Document to create",
   *     required=true,
   *     @OA\MediaType(
   *         mediaType="application/json",
   *         @OA\Schema(
   *             type="object",
   *             ref=@Model(type=Document::class, groups={"write"}),
   *             additionalProperties=true
   *         )
   *     )
   * )
   *
   * @OA\Response(
   *     response=201,
   *     description="Create a Document"
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
   * @OA\Tag(name="documents")
   *
   * @param Request $request
   * @return View
   * @throws \Exception
   */
  public function postDocumentAction(Request $request)
  {
    $this->denyAccessUnlessGranted(['ROLE_OPERATORE','ROLE_ADMIN']);

    $document = new Document();
    $document->setTenant($this->is->getCurrentInstance());
    $document->setDownloadLink($this->generateUrl('document_download', ['id' => $document->getId()], UrlGeneratorInterface::ABSOLUTE_URL));

    $user = $this->getUser();

    if ($user instanceof AdminUser || $user instanceof OperatoreUser) {
      $document->setRecipientType(Document::RECIPIENT_TENANT);
    } else if ($user instanceof CPSUser) {
      $document->setRecipientType(Document::RECIPIENT_USER);
    }


    $form = $this->createForm('App\Form\DocumentAPIType', $document);
    $this->processForm($request, $form);
    if ($form->isSubmitted() && !$form->isValid()) {
      $errors = $this->getErrorsFromForm($form);

      $data = [
        'type' => 'validation_error',
        'title' => 'There was a validation error',
        'errors' => $errors
      ];
      return $this->view($data, Response::HTTP_BAD_REQUEST);
    }

    try {
      $this->em->persist($document);
      $this->em->flush();

    } catch (UniqueConstraintViolationException $e) {
      return $this->view(["Il file " .  $document->getTitle() . " already exists"], Response::HTTP_BAD_REQUEST);
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
    return $this->view($document, Response::HTTP_CREATED);
  }

  /**
   * Edit full Document
   * @Rest\Put("/{id}", name="documents_api_put")
   *
   * @Security(name="Bearer")
   *
   * @OA\RequestBody(
   *     description="The Document to edit",
   *     required=true,
   *     @OA\MediaType(
   *         mediaType="application/json",
   *         @OA\Schema(
   *             type="object",
   *             ref=@Model(type=Document::class, groups={"write"})
   *         )
   *     )
   * )
   *
   * @OA\Response(
   *     response=200,
   *     description="Edit full Document"
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
   * @OA\Tag(name="documents")
   *
   * @param $id
   * @param Request $request
   * @return View
   */
  public function putDocumentAction($id, Request $request)
  {
    $document = $this->em->getRepository('App\Entity\Document')->find($id);

    if (!$document) {
      return $this->view(["Object not found"], Response::HTTP_NOT_FOUND);
    }

    $this->denyAccessUnlessGranted(DocumentVoter::EDIT, $document);

    $form = $this->createForm('App\Form\DocumentAPIType', $document);
    $this->processForm($request, $form);

    if ($form->isSubmitted() && !$form->isValid()) {
      $errors = $this->getErrorsFromForm($form);
      $data = [
        'type' => 'put_validation_error',
        'title' => 'There was a validation error',
        'errors' => $errors
      ];
      return $this->view($data, Response::HTTP_BAD_REQUEST);
    }

    $em = $this->getDoctrine()->getManager();

    try {
      $em->persist($document);
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

    return $this->view(["Object Modified Successfully"], Response::HTTP_OK);
  }

  /**
   * Patch a Document
   * @Rest\Patch("/{id}", name="documents_api_patch")
   *
   * @Security(name="Bearer")
   *
   * @OA\RequestBody(
   *     description="The Document to patch",
   *     required=true,
   *     @OA\MediaType(
   *         mediaType="application/json",
   *         @OA\Schema(
   *             type="object",
   *             ref=@Model(type=Document::class, groups={"write"})
   *         )
   *     )
   * )
   *
   * @OA\Response(
   *     response=200,
   *     description="Patch a Document"
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
   * @OA\Tag(name="documents")
   *
   * @param $id
   * @param Request $request
   * @return View
   */
  public function patchDocumentAction($id, Request $request)
  {

    $document = $this->em->getRepository('App\Entity\Document')->find($id);

    if (!$document) {
      return $this->view(["Object not found"], Response::HTTP_NOT_FOUND);
    }
    $this->denyAccessUnlessGranted(DocumentVoter::EDIT, $document);

    $form = $this->createForm('App\Form\DocumentAPIType', $document);
    $this->processForm($request, $form);

    if ($form->isSubmitted() && !$form->isValid()) {
      $errors = $this->getErrorsFromForm($form);
      $data = [
        'type' => 'validation_error',
        'title' => 'There was a validation error',
        'errors' => $errors
      ];
      return $this->view($data, Response::HTTP_BAD_REQUEST);
    }

    try {
      $em = $this->getDoctrine()->getManager();
      $em->persist($document);
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
   * Delete a Document
   * @Rest\Delete("/{id}", name="document_api_delete")
   *
   * @Security(name="Bearer")
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
   * @OA\Tag(name="documents")
   *
   * @Method("DELETE")
   * @param $id
   * @return View
   */
  public function deleteDocumentAction($id)
  {
    $this->denyAccessUnlessGranted(['ROLE_OPERATORE','ROLE_ADMIN']);

    $document = $this->getDoctrine()->getRepository('App\Entity\Document')->find($id);
    if ($document) {
      // debated point: should we 404 on an unknown nickname?
      // or should we just return a nice 204 in all cases?
      // we're doing the latter
      $em = $this->getDoctrine()->getManager();
      $em->remove($document);
      $em->flush();
    }
    return $this->view(null, Response::HTTP_NO_CONTENT);
  }


  /**
   * Download a document
   * @Rest\Get("/{id}/download", name="document_download")
   *
   * @Security(name="Bearer")
   *
   * @OA\Response(
   *     response=200,
   *     description="Download a document",
   * )
   *
   * @OA\Response(
   *     response=403,
   *     description="Access denied"
   * )
   *
   * @OA\Response(
   *     response=404,
   *     description="Document not found"
   * )
   * @OA\Tag(name="documents")
   *
   * @param Request $request
   * @param $id
   * @return View|Response
   */
  public function downloadDocumentAction(Request $request, $id)
  {
    $document = $this->em->getRepository('App\Entity\Document')->find($id);

    $this->denyAccessUnlessGranted(DocumentVoter::VIEW, $document);

    if (!$document) {
      return $this->view(["Object not found"], Response::HTTP_NOT_FOUND);
    }

    try {
      return $this->documentManager->download($document);
    } catch (\Exception $exception) {
      $data = [
        'type' => 'error',
        'title' => 'There was an error during save process',
        'description' => 'Contact technical support at support@opencontent.it'
      ];
      $this->logger->error($exception->getMessage(), ['request' => $request]);
      return $this->view($data, Response::HTTP_INTERNAL_SERVER_ERROR);
    }
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
