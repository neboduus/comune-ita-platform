<?php


namespace App\Controller\Ui\Frontend;


use App\Logging\LogConstants;
use App\Services\BreadcrumbsService;
use App\Services\FileService\DocumentFileService;
use Doctrine\DBAL\Driver\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMException;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use WhiteOctober\BreadcrumbsBundle\Model\Breadcrumbs;

/**
 * Class DocumentController
 * @Route("documenti")
 */
class DocumentController extends AbstractController
{
  /**
   * @var EntityManagerInterface
   */
  private $em;

  /**
   * @var LoggerInterface
   */
  private $logger;
  /**
   * @var TranslatorInterface
   */
  private $translator;
  /**
   * @var BreadcrumbsService
   */
  private $breadcrumbsService;
  /**
   * @var DocumentFileService
   */
  private $fileService;

  /**
   * DocumentController constructor.
   * @param TranslatorInterface $translator
   * @param EntityManagerInterface $em
   * @param LoggerInterface $logger
   * @param BreadcrumbsService $breadcrumbsService
   * @param DocumentFileService $fileService
   */
  public function __construct(TranslatorInterface $translator, EntityManagerInterface $em, LoggerInterface $logger, BreadcrumbsService $breadcrumbsService, DocumentFileService $fileService)
  {
    $this->translator = $translator;
    $this->em = $em;
    $this->logger = $logger;
    $this->breadcrumbsService = $breadcrumbsService;
    $this->fileService = $fileService;

    $this->breadcrumbsService->getBreadcrumbs()->addRouteItem($this->translator->trans('nav.documenti'), 'folders_list_cpsuser');

  }

  /**
   * @Route("/", name="folders_list_cpsuser")
   * @throws Exception|\Doctrine\DBAL\Exception
   */
  public function cpsUserListFoldersAction()
  {

    $user = $this->getUser();
    // Get all user's folders
    $folders = $this->getDoctrine()->getRepository('App\Entity\Folder')->findBy(['owner' => $user]);

    // Get folders with shared documents
    $sql = 'SELECT DISTINCT folder.id from document JOIN folder  on document.folder_id = folder.id where (readers_allowed)::jsonb @> \'"' . $user->getCodiceFiscale() . '"\'';
    $stmt = $this->em->getConnection()->prepare($sql);
    $result = $stmt->executeQuery();
    $sharedIds = $result->fetchAllAssociative();

    foreach ($sharedIds as $id) {
      $folders[] = $this->em->getRepository('App\Entity\Folder')->find($id);
    }

    return $this->render('Document/cpsUserListFolders.html.twig', [
      'folders' => $folders,
      'user' => $this->getUser(),
    ]);
  }

  /**
   * @Route("/{folderId}", name="documenti_list_cpsuser")
   * @param Request $request
   * @param string $folderId
   * @return Response|RedirectResponse
   * @throws \Doctrine\DBAL\Exception
   */
  public function cpsUserListDocumentsAction(Request $request, string $folderId)
  {
    $user = $this->getUser();
    $folder = $this->em->getRepository('App\Entity\Folder')->find($folderId);
    $documents = [];

    if (!$folder) {
      $this->addFlash('warning', $this->translator->trans('documenti.no_folder'));
      return $this->redirectToRoute('folders_list_cpsuser');
    }
    $this->breadcrumbsService->getBreadcrumbs()->addRouteItem($folder->getTitle(), 'documenti_list_cpsuser', ['folderId' => $folderId]);

    if ($folder->getOwner() == $user)
      $documents = $this->getDoctrine()->getRepository('App\Entity\Document')->findBy(['folder' => $folder]);
    else {
      try {
        $sql = 'SELECT document.id from document JOIN folder  on document.folder_id = folder.id where (readers_allowed)::jsonb @> \'"' . $user->getCodiceFiscale() . '"\' and folder.id = \'' . $folder->getId() . '\'';

        $stmt = $this->em->getConnection()->prepare($sql);
        $sharedDocuments = $stmt->executeQuery()->fetchAllAssociative();

        foreach ($sharedDocuments as $id) {
          $documents[] = $this->em->getRepository('App\Entity\Document')->find($id);
        }
      } catch (Exception $exception) {
        $this->addFlash('warning', $this->translator->trans('documenti.document_search_error'));
      }
    }

    return $this->render('Document/cpsUserListDocuments.html.twig', [
      'documents' => $documents,
      'folder' => $folder,
      'user' => $user
    ]);
  }

  /**
   * @Route("/{folderId}/{documentId}", name="documento_show_cpsuser")
   * @param Request $request
   * @param string $folderId
   * @param string $documentId
   * @return RedirectResponse|Response
   */
  public function cpsUserShowDocumentoAction(Request $request, string $folderId, string $documentId)
  {
    $user = $this->getUser();
    $folder = $this->em->getRepository('App\Entity\Folder')->find($folderId);
    $document = $this->em->getRepository('App\Entity\Document')->find($documentId);

    if (!$folder) {
      $this->addFlash('warning', $this->translator->trans('documenti.no_folder'));
      return $this->redirectToRoute('folders_list_cpsuser');
    } elseif (!$document) {
      $this->addFlash('warning', $this->translator->trans('documenti.no_document'));
      return $this->redirectToRoute('documenti_list_cpsuser', ['folderId' => $folderId]);
    }

    $this->breadcrumbsService->getBreadcrumbs()->addRouteItem($folder->getTitle(), 'documenti_list_cpsuser', ['folderId' => $folderId]);
    $this->breadcrumbsService->getBreadcrumbs()->addItem($document->getTitle());

    if ($folder->getOwner() == $user || in_array($user->getCodiceFiscale(), (array)$document->getReadersAllowed())) {
      return $this->render('Document/cpsUserShowDocumento.html.twig', [
        'document' => $document,
        'user' => $user,
      ]);
    } else {
      $this->addFlash('warning', $this->translator->trans('documenti.no_document_permissions'));
      return $this->redirectToRoute('documenti_list_cpsuser', ['folderId' => $folderId]);
    }
  }

  /**
   * Download a document
   * @Route("/{folderId}/{documentId}/download", name="document_download_cpsuser")
   * @param Request $request
   * @param string $folderId
   * @param string $documentId
   * @return Response
   * @throws \Exception
   */
  public function downloadDocumentAction(Request $request, string $folderId, string $documentId): Response
  {
    $user = $this->getUser();
    $folder = $this->em->getRepository('App\Entity\Folder')->find($folderId);
    $document = $this->em->getRepository('App\Entity\Document')->find($documentId);

    if ($folder->getOwner() != $user && !in_array($user->getCodiceFiscale(), (array)$document->getReadersAllowed())) {
      return new Response(null, Response::HTTP_UNAUTHORIZED);
    }

    $response = $this->fileService->download($document);
    try {
      $document->setLastReadAt(new \DateTime());
      $document->setDownloadsCounter($document->getDownloadsCounter() + 1);
      $this->em->persist($document);
      $this->em->flush();

    } catch (ORMException $e) {
      $this->logger->notice(
        LogConstants::DOCUMENT_UPDATE_ERROR, ['document' => $document]
      );
    }
    return $response;
  }
}
