<?php


namespace App\Services\Manager;


use App\Entity\Document;
use App\Services\FileService;
use League\Flysystem\FileExistsException;
use League\Flysystem\FilesystemInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class DocumentManager
{

  const FILE_PATH = '/documents/users/';
  const URI_PREFIX = '/uploads';
  /**
   * @var FileService
   */
  private $fileService;

  /**
   * DocumentManager constructor.
   * @param FileService $fileService
   */
  public function __construct(FileService $fileService)
  {
    $this->fileService = $fileService;
  }

  /**
   * @throws FileExistsException
   */
  public function save(Document $document, $content = null)
  {
    $filePath = $this->getFilenameWithPath($document);
    if (!$content && $document->getAddress()) {
      $content = file_get_contents($document->getAddress());
    }

    $this->fileService->write($filePath, $content);
  }

  public function getFilePath(Document $document): string
  {
    $extension = explode('.', $document->getOriginalFilename());
    $extension = end($extension);
    return self::FILE_PATH . $document->getOwnerId() . DIRECTORY_SEPARATOR . $document->getFolderId() . DIRECTORY_SEPARATOR . $document->getId() . '.' . $extension;
  }

  public function getFilenameWithPath(Document $document, $absolute = false): string
  {
    $filePath = $this->getFilePath($document);
    if ($absolute) {
      $prefix = str_replace('/', '', self::URI_PREFIX);
      $filenameWithPath = sprintf('%s/%s', $prefix, $filePath);
    } else {
      $filenameWithPath = $filePath;
    }
    return str_replace('//', '/', $filenameWithPath);
  }

  /**
   * @param Document $document
   * @return Response
   */
  public function download(Document $document)
  {
    if ($document->isStore()) {
      return $this->createPresignedRequest($document);
    } else {
      return $this->createBinaryResponse($document);
    }
  }

  /**
   * @param Document $document
   * @return RedirectResponse
   */
  private function createPresignedRequest(Document $document): RedirectResponse
  {
    $request = $this->fileService->getPresignedRequest(
      $this->getFilenameWithPath($document, true),
      $document->getMimeType(),
      $document->getOriginalFilename()
    );
    return new RedirectResponse((string)$request->getUri());
  }

  /**
   * @param Document $document
   * @return Response
   */
  private function createBinaryResponse(Document $document): Response
  {
    $response = new Response(file_get_contents($document->getAddress()));
    // Set file Content-Type
    $mimeType = $document->getMimeType();
    $response->headers->set('Content-Type', $mimeType);

    // Create the disposition of the file
    $filename = $document->getOriginalFilename();
    $disposition = $response->headers->makeDisposition(
      ResponseHeaderBag::DISPOSITION_ATTACHMENT,
      $filename
    );

    $response->headers->set('Content-Disposition', $disposition);
    return $response;
  }

}
