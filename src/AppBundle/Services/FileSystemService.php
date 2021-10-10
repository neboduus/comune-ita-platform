<?php

namespace AppBundle\Services;

use League\Flysystem\FilesystemInterface;

class FileSystemService
{
  /**
   * @var FilesystemInterface
   */
  private $filesystem;

  public function __construct(FilesystemInterface $filesystem)
  {
    $this->filesystem = $filesystem;
  }

  public function getFilesystem(): FilesystemInterface
  {
    return $this->filesystem;
  }

  public function setFilesystem(FilesystemInterface $filesystem): void
  {
    $this->filesystem = $filesystem;
  }
}
