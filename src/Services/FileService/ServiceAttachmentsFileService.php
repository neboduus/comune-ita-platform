<?php

namespace App\Services\FileService;

use App\Entity\ServiceGroup;
use App\Entity\Servizio;
use App\Model\PublicFile;
use App\Utils\StringUtils;
use Aws\S3\S3Client;
use League\Flysystem\FileExistsException;
use League\Flysystem\FileNotFoundException;
use League\Flysystem\FilesystemInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ServiceAttachmentsFileService extends AbstractFileService
{
  const FILE_PATH = '/services/';
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

  public function getFilePath($filename, $ownerClass, $type=null): string
  {
      $filename = StringUtils::sanitizeFileName($filename);

      if ($ownerClass instanceof Servizio) {
        $filePath = '/services/' . $ownerClass->getId() . DIRECTORY_SEPARATOR ;
      } elseif ($ownerClass instanceof ServiceGroup) {
        $filePath = '/service-groups/'  . $ownerClass->getId() . DIRECTORY_SEPARATOR ;
      } else {
        throw new \InvalidArgumentException("Invalid owner class");
      }

      return $type ? $filePath . $type . DIRECTORY_SEPARATOR . $filename : $filePath . $filename;
  }

  public function getFilenameWithPath($filename, $ownerClass, $type=null, $absolute = false): string
  {
    $filePath = $this->getFilePath($filename, $ownerClass, $type);
    if ($absolute) {
      $prefix = str_replace('/', '', self::URI_PREFIX);
      $filenameWithPath = sprintf('%s/%s', $prefix, $filePath);
    } else {
      $filenameWithPath = $filePath;
    }
    return str_replace('//', '/', $filenameWithPath);
  }

  /**
   * @param UploadedFile $file
   * @param null $ownerClass
   * @param null $type
   * @return PublicFile
   * @throws FileExistsException
   */
  public function save(UploadedFile $file, $ownerClass=null, $type=null): PublicFile
  {
    $filePath = $this->getFilenameWithPath($file->getClientOriginalName(), $ownerClass, $type);
    $content = file_get_contents($file->getRealPath());

    $this->write($filePath, $content);

    $publicFile = new PublicFile();

    $publicFile->setName(StringUtils::sanitizeFileName($file->getClientOriginalName()));
    $publicFile->setOriginalName($file->getClientOriginalName());
    $publicFile->setType($type);
    $publicFile->setSize($file->getSize());
    $publicFile->setMimeType($file->getMimeType());

    return $publicFile;
  }

  /**
   * @param $filename
   * @param $ownerClass
   * @param $type
   * @return void
   * @throws FileNotFoundException
   */
  public function deleteFilename($filename, $ownerClass, $type)
  {
    $filePath = $this->getFilenameWithPath($filename, $ownerClass, $type);
    $this->delete($filePath);
  }

  /**
   * @param $filename
   * @param $ownerClass
   * @param $type
   * @return RedirectResponse|Response|StreamedResponse
   * @throws FileNotFoundException
   */
  public function download($filename, $ownerClass, $type)
  {
    $absoluteFilePath = $this->getFilenameWithPath($filename, $ownerClass, $type, true);
    $filePath = $this->getFilenameWithPath($filename, $ownerClass, $type);
    $dispositionFilename =  $this->getDispositionFilename($filename);

    if ($this->isAllowedPresignedRequest()) {
      return $this->createPresignedRequest($absoluteFilePath, $filePath, $dispositionFilename);
    } else {
      return $this->createBinaryResponse($filePath, $dispositionFilename);
    }
  }
}
