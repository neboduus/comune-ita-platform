<?php


namespace AppBundle\Controller;


use AppBundle\Logging\LogConstants;
use AppBundle\Services\InstanceService;
use Doctrine\DBAL\DBALException;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMException;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class DocumentController
 * @Route("documenti")
 */
class DocumentController extends Controller
{
  /**
   * @var EntityManager
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

  public function __construct(TranslatorInterface $translator, EntityManager $em, LoggerInterface $logger)
  {
    $this->translator = $translator;
    $this->em = $em;
    $this->logger = $logger;
  }

  /**
   * @Route("/", name="folders_list_cpsuser")
   * @Template()
   */
  public function cpsUserListFoldersAction()
  {
    $user = $this->getUser();
    // Get all user's folders
    $folders = $this->getDoctrine()->getRepository('AppBundle:Folder')->findBy(['owner' => $user]);

    // Get folders with shared documents
    $sql = 'SELECT DISTINCT folder.id from document JOIN folder  on document.folder_id = folder.id where (readers_allowed)::jsonb @> \'"' . $user->getCodiceFiscale() . '"\'';
    $stmt = $this->em->getConnection()->prepare($sql);
    $stmt->execute();
    $sharedIds = $stmt->fetchAll();

    foreach ($sharedIds as $id) {
      $folders[] = $this->em->getRepository('AppBundle:Folder')->find($id);
    }

    return [
      'folders' => $folders,
      'user' => $this->getUser(),
    ];
  }

  /**
   * @Route("/{folderSlug}", name="documenti_list_cpsuser")
   * @Template()
   * @param Request $request
   * @param string $folderSlug
   * @return array|Response
   */
  public function cpsUserListDocumentsAction(Request $request, $folderSlug)
  {
    $user = $this->getUser();
    $folder = $this->em->getRepository('AppBundle:Folder')->findOneBy(['slug'=>$folderSlug, 'owner' => $user]);
    $documents = [];

    if (!$folder) {
      $this->addFlash('warning', $this->translator->trans('documenti.no_folder'));
      return $this->redirectToRoute('folders_list_cpsuser');
    }

      if ($folder->getOwner() == $user)
        $documents = $this->getDoctrine()->getRepository('AppBundle:Document')->findBy(['folder' => $folder]);
      else {
        try {
          $sql = 'SELECT document.id from document JOIN folder  on document.folder_id = folder.id where (readers_allowed)::jsonb @> \'"' . $user->getCodiceFiscale() . '"\' and folder.id = \'' . $folder->getId() . '\'';

          $stmt = $this->em->getConnection()->prepare($sql);
          $stmt->execute();
          $sharedDocuments = $stmt->fetchAll();

          foreach ($sharedDocuments as $id) {
            $documents[] = $this->em->getRepository('AppBundle:Document')->find($id);
          }
        } catch (DBALException $exception) {
          $this->addFlash('warning', $this->translator->trans('documenti.document_search_error'));
        }
      }

    return [
      'documents' => $documents,
      'folder' => $folder,
      'user' => $user
    ];
  }

  /**
   * @Route("/{folderSlug}/{documentId}", name="documento_show_cpsuser")
   * @Template()
   * @param Request $request
   * @param string $folderSlug
   * @param string $documentId
   * @return array|Response
   */
  public function cpsUserShowDocumentoAction(Request $request, $folderSlug, $documentId)
  {
    $user = $this->getUser();
    $folder = $this->em->getRepository('AppBundle:Folder')->findOneBy(['slug'=>$folderSlug, 'owner' => $user]);
    $document = $this->em->getRepository('AppBundle:Document')->find($documentId);

    if (!$folder) {
      $this->addFlash('warning', $this->translator->trans('documenti.no_folder'));
      return $this->redirectToRoute('folders_list_cpsuser');
    } elseif (!$document) {
      $this->addFlash('warning', $this->translator->trans('documenti.no_document'));
      return $this->redirectToRoute('documenti_list_cpsuser', ['folderId'=>$folderId]);
    }

    if ($folder->getOwner() == $user->getCodiceFiscale() || in_array($user->getCodiceFiscale(), (array)$document->getReadersAllowed())) {
      return [
        'document' => $document,
        'user' => $user,
      ];
    } else {
      $this->addFlash('warning', $this->translator->trans('documenti.no_document_permissions'));
      return $this->redirectToRoute('documenti_list_cpsuser', ['folderId'=>$folderId]);
    }
  }

  /**
   * Download a document
   * @Route("/{folderSlug}/{documentId}/download", name="document_download_cpsuser")
   * @param Request $request
   * @param string $folderSlug
   * @param string $documentId
   * @return Response
   * @throws \Exception
   */
  public function downloadDocumentAction(Request $request, $folderSlug,  $documentId)
  {
    $user = $this->getUser();
    $folder = $this->em->getRepository('AppBundle:Folder')->findOneBy(['slug'=>$folderSlug, 'owner' => $user]);
    $document = $this->em->getRepository('AppBundle:Document')->find($documentId);

    if ($folder->getOwner() != $user->getCodiceFiscale() && !in_array($user->getCodiceFiscale(), (array)$document->getReadersAllowed())) {
      return new Response(null, Response::HTTP_UNAUTHORIZED);
    }

    $extension = explode('.', $document->getOriginalFilename());
    $extension = end($extension);

    $filePath = '../var/uploads/documents/users/' .
      $document->getOwnerId() . DIRECTORY_SEPARATOR . $document->getFolderId() .
      DIRECTORY_SEPARATOR . $document->getId() . '.' . $extension;

    if (!file_exists($filePath) && $document->getAddress()) {
      $filePath = $document->getAddress();
    }

    try {
      $fileContent = file_get_contents($filePath);
    } catch (\Exception $exception) {
      return new Response(null, Response::HTTP_NOT_FOUND);
    }

    // Provide a name for your file with extension
    $filename = $document->getOriginalFilename();
    // Return a response with a specific content
    $response = new Response($fileContent);
    // Create the disposition of the file
    $disposition = $response->headers->makeDisposition(
      ResponseHeaderBag::DISPOSITION_ATTACHMENT,
      $filename
    );
    // Set the content disposition
    $response->headers->set('Content-Disposition', $disposition);
    // Set the content type
    $response->headers->set('Content-Type', $document->getMimeType());

    try {
      $document->setLastReadAt( new \DateTime());
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
