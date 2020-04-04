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
    /**
     * @param object          $object
     * @param PropertyMapping $mapping
     * @return string
     */
    public function directoryName($object, PropertyMapping $mapping):string
    {
        if ($object instanceof Allegato) {
          $owner = $object->getOwner();
          if ($owner instanceof User) {
            return $object->getOwner()->getId();
          } else {
            return date('Y/m-d/Hi');
          }
        }

        return 'misc';
    }
}
