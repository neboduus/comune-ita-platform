<?php

namespace App\Services\FileService;

use App\Entity\Allegato;
use Aws\S3\S3Client;
use League\Flysystem\FileNotFoundException;
use League\Flysystem\FilesystemInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Vich\UploaderBundle\Mapping\PropertyMappingFactory;

class AllegatoFileService extends AbstractFileService
{
  /**
   * @var PropertyMappingFactory
   */
  private $propertyMappingFactory;

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
  )
  {
    parent::__construct($fileSystem, $s3Client, $s3Bucket, $uploadDestination);
    $this->propertyMappingFactory = $propertyMappingFactory;
  }

  /**
   * @param Allegato $allegato
   * @return string
   */
  public function getPath(Allegato $allegato): string
  {
    $mapping = $this->propertyMappingFactory->fromField($allegato, 'file');
    return $mapping->getDirectoryNamer()->directoryName($allegato, $mapping);
  }

  /**
   * @param Allegato $allegato
   * @return string
   */
  public function getName(Allegato $allegato): string
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
   * @return array
   * @throws FileNotFoundException
   */
  public function getAttachmentData(Allegato $allegato): array
  {
    $filenameWithPath = $this->getFilenameWithPath($allegato);
    $type = $allegato->getType();
    return [
      'id' => $allegato->getId(),
      'name' => $allegato->getOriginalFileName(),
      'type' => $type,
      'size' => $this->fileSystem->getSize($filenameWithPath),
      'updated' => $allegato->getUpdatedAt(),
      'path' => $filenameWithPath,
    ];
  }

  public function getFilenameWithPath(Allegato $allegato, bool $absolute = false): string
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
    return str_replace('//', '/', $filenameWithPath);
  }

  /**
   * @param Allegato $allegato
   * @return RedirectResponse|Response|StreamedResponse
   * @throws FileNotFoundException
   */
  public function download(Allegato $allegato)
  {
    $filenameWithPath = $this->getFilenameWithPath($allegato);
    $dispositionFilename =  $this->getDispositionFilename($allegato->getFilename(), $allegato->getOriginalFilename());

    if ($this->isAllowedPresignedRequest()) {
      return $this->createPresignedRequest(
        $this->getFilenameWithPath($allegato, true),
        $filenameWithPath,
        $dispositionFilename
      );
    } else {
      return $this->createBinaryResponse($filenameWithPath, $dispositionFilename);
    }
  }

  /**
   * @param Allegato $allegato
   * @return string
   */
  public function getPresignedPostRequestUri(Allegato $allegato): string
  {
    $request = $this->createPresignedPostRequest(
      $this->getFilenameWithPath($allegato, true),
      $allegato->getMimeType() ?? Allegato::DEFAULT_MIME_TYPE
    );
    return (string)$request->getUri();
  }

  /**
   * @param Allegato $allegato
   * @return false|string
   * @throws FileNotFoundException
   */
  public function getAttachmentContent(Allegato $allegato)
  {
    $filenameWithPath = $this->getFilenameWithPath($allegato);
    return $this->getContent($filenameWithPath);
  }
}
