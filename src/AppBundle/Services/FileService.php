<?php

namespace AppBundle\Services;

use AppBundle\Entity\Allegato;
use Doctrine\ORM\EntityManagerInterface;
use League\Flysystem\FileNotFoundException;
use League\Flysystem\FilesystemInterface;
use Vich\UploaderBundle\Mapping\PropertyMappingFactory;

class FileService
{
  private $propertyMappingFactory;
  private $fileSystem;

  /**
   * @param PropertyMappingFactory $propertyMappingFactory
   * @param FilesystemInterface $fileSystem
   * @param EntityManagerInterface $entityManager
   */
  public function __construct(PropertyMappingFactory $propertyMappingFactory, FilesystemInterface $fileSystem) {
    $this->propertyMappingFactory = $propertyMappingFactory;
    $this->fileSystem = $fileSystem;
  }

  /**
   * @param Allegato $allegato
   * @return string
   */
  private function getFilenameWithPath(Allegato $allegato)
  {
    $mapping = $this->propertyMappingFactory->fromField($allegato, 'file');
    $prefix = $mapping->getDirectoryNamer()->directoryName($allegato, $mapping);
    $filenameWithPath = sprintf('%s/%s', $prefix, $allegato->getFileName());
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
      'path' => $filenameWithPath
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
   * @return bool
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

}
