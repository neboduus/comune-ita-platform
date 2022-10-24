<?php

namespace App\Services\FileService;

use App\Entity\Document;
use Aws\S3\S3Client;
use League\Flysystem\FileExistsException;
use League\Flysystem\FileNotFoundException;
use League\Flysystem\FilesystemInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;


class DocumentFileService extends AbstractFileService
{
  const FILE_PATH = '/documents/users/';
  const URI_PREFIX = '/uploads';

  /**
   * @param FilesystemInterface $fileSystem
   * @param S3Client $s3Client
   * @param string $s3Bucket
   * @param string $uploadDestination
   */
  public function __construct(
    FilesystemInterface $fileSystem,
    S3Client $s3Client,
    string $s3Bucket,
    string $uploadDestination
  )
  {
    parent::__construct($fileSystem, $s3Client, $s3Bucket, $uploadDestination);
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
   * @throws FileExistsException
   */
  public function save(Document $document, $content = null)
  {
    $filePath = $this->getFilenameWithPath($document);
    if (!$content && $document->getAddress()) {
      $content = file_get_contents($document->getAddress());
    }

    $this->write($filePath, $content);
  }

  /**
   * @param Document $document
   * @return Response
   * @throws FileNotFoundException
   */
  public function download(Document $document): Response
  {
    $dispositionFilename =  $this->getDispositionFilename($document->getOriginalFilename());

    if ($document->isStore()) {
      return $this->createPresignedRequest(
        $this->getFilenameWithPath($document, true),
        $this->getFilenameWithPath($document),
        $dispositionFilename
      );
    } else {
      $content = file_get_contents($document->getAddress());
      return $this->createBinaryResponseFromContent($content, $document->getMimeType(), $dispositionFilename);
    }
  }

}
