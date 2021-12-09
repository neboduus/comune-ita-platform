<?php

namespace AppBundle\Services;

use AppBundle\Entity\Allegato;
use AppBundle\Utils\StringUtils;
use Aws\S3\S3Client;
use League\Flysystem\FileNotFoundException;
use League\Flysystem\FilesystemInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Vich\UploaderBundle\Mapping\PropertyMappingFactory;

class FileService
{

  const PRESIGNED_GET_EXPIRE_STRING = '+30 minutes';
  const PRESIGNED_PUT_EXPIRE_STRING = '+120 minutes';


  /**
   * @var PropertyMappingFactory
   */
  private $propertyMappingFactory;

  /**
   * @var FilesystemInterface
   */
  private $fileSystem;

  /**
   * @var S3Client
   */
  private $s3Client;

  /**
   * @var string
   */
  private $s3Bucket;

  /**
   * @var string
   */
  private $uploadDestination;

  /**
   * @param PropertyMappingFactory $propertyMappingFactory
   * @param FilesystemInterface $fileSystem
   * @param S3Client $s3Client
   * @param string $s3Bucket
   * @param string $uploadDestination
   */
  public function __construct(
    PropertyMappingFactory $propertyMappingFactory,
    FilesystemInterface $fileSystem,
    S3Client $s3Client,
    string $s3Bucket,
    string $uploadDestination
  ) {
    $this->propertyMappingFactory = $propertyMappingFactory;
    $this->fileSystem = $fileSystem;
    $this->s3Client = $s3Client;
    $this->s3Bucket = $s3Bucket;
    $this->uploadDestination = $uploadDestination;
  }

  /**
   * @param Allegato $allegato
   * @return string
   */
  public function getPath(Allegato $allegato)
  {
    $mapping = $this->propertyMappingFactory->fromField($allegato, 'file');
    $filePath = $mapping->getDirectoryNamer()->directoryName($allegato, $mapping);

    return $filePath;
  }

  /**
   * @param Allegato $allegato
   * @return string
   */
  public function getName(Allegato $allegato)
  {
    $name = \str_replace('.', '', \uniqid('', true));
    $fileNameParts = explode('.', $allegato->getOriginalFilename());
    $extension = end($fileNameParts);

    if (\is_string($extension) && '' !== $extension) {
      $name = \sprintf('%s.%s', $name, $extension);
    }

    return $name;
  }

  /**
   * @param Allegato $allegato
   * @param bool $absolute
   * @return string
   */
  public function getFilenameWithPath(Allegato $allegato, $absolute = false)
  {
    $mapping = $this->propertyMappingFactory->fromField($allegato, 'file');
    $filePath = $mapping->getDirectoryNamer()->directoryName($allegato, $mapping);
    if ($absolute) {
      $prefix = str_replace('/', '', $mapping->getUriPrefix());
      $filenameWithPath = sprintf('%s/%s/%s', $prefix, $filePath, $allegato->getFileName());
    } else {
      $filenameWithPath = sprintf('%s/%s', $filePath, $allegato->getFileName());
    }
    // Todo: correggere impostazioni e testare upload
    $filenameWithPath = str_replace('//', '/', $filenameWithPath);
    return $filenameWithPath;
  }

  /**
   * @param Allegato $allegato
   * @return array
   * @throws FileNotFoundException
   */
  public function getAttachmentData(Allegato $allegato): array
  {
    $filenameWithPath = $this->getFilenameWithPath($allegato);
    $type = $allegato->getType();
    $values = [
      'id' => $allegato->getId(),
      'name' => $allegato->getOriginalFileName(),
      'type' => $type,
      'size' => $this->fileSystem->getSize($filenameWithPath),
      'updated' => $allegato->getUpdatedAt(),
      'path' => $filenameWithPath,
    ];

    return $values;
  }

  /**
   * @param Allegato $allegato
   * @return false|string
   * @throws FileNotFoundException
   */
  public function getAttachmentContent(Allegato $allegato)
  {
    $filenameWithPath = $this->getFilenameWithPath($allegato);

    return $this->fileSystem->read($filenameWithPath);
  }

  /**
   * @param Allegato $allegato
   * @return false|resource
   * @throws FileNotFoundException
   */
  public function getAttachmentStream(Allegato $allegato)
  {
    $filenameWithPath = $this->getFilenameWithPath($allegato);

    return $this->fileSystem->readStream($filenameWithPath);
  }


  /**
   * @param Allegato $allegato
   * @return array|string|string[]
   * @throws FileNotFoundException
   */
  public function getHash(Allegato $allegato)
  {
    $filenameWithPath = $this->getFilenameWithPath($allegato);
    $metadata = $this->fileSystem->getMetadata($filenameWithPath);
    if (isset($metadata['etag'])) {
      return str_replace('"', '', $metadata['etag']);
    } else {
      return '-';
    }
  }

  /**
   * @param Allegato $allegato
   * @return bool
   * @throws FileNotFoundException
   */
  public function getMimeType(Allegato $allegato)
  {
    $filenameWithPath = $this->getFilenameWithPath($allegato);

    return $this->fileSystem->getMimetype($filenameWithPath);
  }

  /**
   * @param Allegato $allegato
   * @return bool
   */
  public function fileExist(Allegato $allegato)
  {
    $filenameWithPath = $this->getFilenameWithPath($allegato);

    return $this->fileSystem->has($filenameWithPath);
  }

  /**
   * @param Allegato $allegato
   * @return Response
   */
  public function download(Allegato $allegato)
  {
    if ($this->isAllowedPresignedRequest()) {
      return $this->createPresignedRequest($allegato);
    } else {
      return $this->createBinaryResponse($allegato);
    }
  }

  /**
   * @param Allegato $allegato
   * @return RedirectResponse
   */
  private function createPresignedRequest(Allegato $allegato)
  {
    $responseHeaderBag = new ResponseHeaderBag();
    $filename = $this->getDispositionFilename($allegato);
    $disposition = $responseHeaderBag->makeDisposition(
      ResponseHeaderBag::DISPOSITION_ATTACHMENT,
      $filename
    );

    $command = $this->s3Client->getCommand('GetObject', [
      'Bucket' => $this->s3Bucket,
      'Key' => $this->getFilenameWithPath($allegato, true),
      'ResponseContentType' => $allegato->getMimeType() ?? $this->getMimeType($allegato),
      'ResponseContentDisposition' => $disposition,
    ]);
    $request = $this->s3Client->createPresignedRequest($command, self::PRESIGNED_GET_EXPIRE_STRING);

    return new RedirectResponse((string)$request->getUri());
  }

  public function createPresignedPostRequest(Allegato $allegato)
  {
    $command = $this->s3Client->getCommand('PutObject', [
      'Bucket' => $this->s3Bucket,
      'Key' => $this->getFilenameWithPath($allegato, true),
      'Content-Type' => $allegato->getMimeType() ?? Allegato::DEFAULT_MIME_TYPE
    ]);
    $request = $this->s3Client->createPresignedRequest($command, self::PRESIGNED_PUT_EXPIRE_STRING)->withMethod('PUT');

    return (string)$request->getUri();
  }


  /**
   * @param Allegato $allegato
   * @return Response
   */
  private function createBinaryResponse(Allegato $allegato)
  {
    try {
      $fileService = $this;
      $response = new StreamedResponse(function () use ($allegato, $fileService) {
        $outputStream = fopen('php://output', 'wb');
        $fileStream = $fileService->getAttachmentStream($allegato);
        stream_copy_to_stream($fileStream, $outputStream);
      });
      // Set file Content-Type
      $mimeType = $this->getMimetype($allegato);
      $response->headers->set('Content-Type', $mimeType);

      // Create the disposition of the file
      $filename = $this->getDispositionFilename($allegato);
      $disposition = $response->headers->makeDisposition(
        ResponseHeaderBag::DISPOSITION_ATTACHMENT,
        $filename
      );

      $response->headers->set('Content-Disposition', $disposition);
      return $response;

    } catch (FileNotFoundException $exception) {
      return new Response('File not found!', Response::HTTP_NOT_FOUND);
    }
  }

  /**
   * @param Allegato $allegato
   * @return array|string|string[]|null
   */
  private function getDispositionFilename(Allegato $allegato)
  {
    $filename = StringUtils::clean(mb_convert_encoding($allegato->getOriginalFilename(), "ASCII", "auto"));
    $filenameParts = explode('.', $filename);

    $systemFilename = $allegato->getFilename();
    $systemFilenameParts = explode('.', $systemFilename);
    if (end($filenameParts) != end($systemFilenameParts)) {
      $filename .=  '.' . end($systemFilenameParts);
    }

    return $filename;
  }

  /**
   * @return bool
   */
  private function isAllowedPresignedRequest()
  {
    return !empty($this->s3Bucket) && $this->uploadDestination === 's3_filesystem';
  }

}
