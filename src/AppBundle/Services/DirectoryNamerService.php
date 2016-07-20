<?php
namespace AppBundle\Services;

use AppBundle\Entity\Allegato;
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
            return $object->getOwner()->getId();
        }

        return 'misc';
    }
}
