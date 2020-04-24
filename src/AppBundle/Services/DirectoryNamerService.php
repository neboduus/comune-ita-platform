<?php

namespace AppBundle\Services;

use AppBundle\Entity\Allegato;
use AppBundle\Entity\User;
use Vich\UploaderBundle\Mapping\PropertyMapping;
use Vich\UploaderBundle\Naming\DirectoryNamerInterface;

/**
 * Class CPSAllegatiDirectoryNamer
 */
class DirectoryNamerService implements DirectoryNamerInterface
{

  private $charsPerDir = 2;
  private $dirs = 3;

  /**
   * @param object $object
   * @param PropertyMapping $mapping
   * @return string
   */
  public function directoryName($object, PropertyMapping $mapping): string
  {
    if ($object instanceof Allegato) {
      $owner = $object->getOwner();
      if ($owner instanceof User) {
        return $object->getOwner()->getId();
      } else {
        $fileName = $mapping->getFileName($object);
        $parts = [];
        for ($i = 0, $start = 0; $i < $this->dirs; $i++, $start += $this->charsPerDir) {
          $parts[] = \substr($fileName, $start, $this->charsPerDir);
        }
        return \implode('/', $parts);
      }
    }
    return 'misc';
  }
}
