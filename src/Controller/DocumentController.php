<?php

namespace App\Controller;

use App\Logging\LogConstants;
use App\Multitenancy\TenantAwareController;
use Doctrine\DBAL\DBALException;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;
use App\Multitenancy\Annotations\MustHaveTenant;

/**
 * @todo corregge query in dql
 * Class DocumentController
 * @Route("documenti")
 * @MustHaveTenant()
 */
class DocumentController extends TenantAwareController
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(EntityManagerInterface $em, LoggerInterface $logger)
    {
        $this->em = $em;
        $this->logger = $logger;
    }

    /**
     * @Route("/", name="folders_list_cpsuser")
     * @return Response
     * @throws DBALException
     */
    public function cpsUserListFolders()
    {
        $user = $this->getUser();
        // Get all user's folders
        $folders = $this->getDoctrine()->getRepository('App:Folder')->findBy(['owner' => $user]);

        // Get folders with shared documents
        $sql = 'SELECT DISTINCT folder.id from document JOIN folder on document.folder_id = folder.id where (readers_allowed)::jsonb @> \'"' . $user->getCodiceFiscale() . '"\'';
        $stmt = $this->em->getConnection()->prepare($sql);
        $stmt->execute();
        $sharedIds = $stmt->fetchAll();

        foreach ($sharedIds as $id) {
            $folders[] = $this->em->getRepository('App:Folder')->find($id);
        }

        return $this->render('Document/cpsUserListFolders.html.twig', [
            'folders' => $folders,
            'user' => $this->getUser(),
        ]);
    }

    /**
     * @Route("/{folderId}", name="documenti_list_cpsuser")
     * @param string $folderId
     * @return Response
     */
    public function cpsUserListDocuments($folderId)
    {
        $user = $this->getUser();
        $folder = $this->em->getRepository('App:Folder')->find($folderId);
        if (!$folder) {
            return new Response(null, Response::HTTP_NOT_FOUND);
        }

        $documents = [];
        if ($folder->getOwner() == $user) {
            $documents = $this->getDoctrine()->getRepository('App:Document')->findBy(['folder' => $folder]);
        } else {
            try {
                $sql = 'SELECT document.id from document JOIN folder  on document.folder_id = folder.id where (readers_allowed)::jsonb @> \'"' . $user->getCodiceFiscale() . '"\' and folder.id = \'' . $folder->getId() . '\'';

                $stmt = $this->em->getConnection()->prepare($sql);
                $stmt->execute();
                $sharedDocuments = $stmt->fetchAll();

                foreach ($sharedDocuments as $id) {
                    $documents[] = $this->em->getRepository('App:Document')->find($id);
                }
            } catch (DBALException $exception) {
                $this->addFlash('warning', 'Si è verificato un errore dirante la ricerca dei documenti');
            }
        }

        return $this->render('Document/cpsUserListDocuments.html.twig', [
            'documents' => $documents,
            'folder' => $folder,
            'user' => $this->getUser(),
        ]);
    }

    /**
     * @Route("/{folderId}/{documentId}", name="documento_show_cpsuser")
     * @param string $folderId
     * @param string $documentId
     * @return array|Response
     */
    public function cpsUserShowDocumento($folderId, $documentId)
    {
        $user = $this->getUser();
        $folder = $this->em->getRepository('App:Folder')->find($folderId);
        $document = $this->em->getRepository('App:Document')->find($documentId);

        if (!$folder || !$document) {
            return new Response(null, Response::HTTP_NOT_FOUND);
        }

        if ($folder->getOwner() == $user->getCodiceFiscale() || in_array($user->getCodiceFiscale(), (array)$document->getReadersAllowed())) {
            return $this->render('Document/cpsUserShowDocumento.html.twig', [
                'document' => $document,
                'user' => $this->getUser(),
            ]);
        } else {
            return new Response(null, Response::HTTP_UNAUTHORIZED);
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
    public function downloadDocument(Request $request, $folderId, $documentId)
    {
        $user = $this->getUser();
        $folder = $this->em->getRepository('App:Folder')->find($folderId);
        $document = $this->em->getRepository('App:Document')->find($documentId);

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
            $document->setLastReadAt(new \DateTime());
            $document->setDownloadsCounter($document->getDownloadsCounter() + 1);
            $this->em->persist($document);
            $this->em->flush();
        } catch (ORMException $e) {
            $this->logger->notice(
                LogConstants::DOCUMENT_UPDATE_ERROR,
                ['document' => $document]
            );
        }
        return $response;
    }
}
