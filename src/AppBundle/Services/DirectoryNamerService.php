<?php

namespace AppBundle\Services;

use AppBundle\Entity\Allegato;
use AppBundle\Entity\Pratica;
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
      if ($owner instanceof User && $this->isUserAwareAllegato($object)) {

        return $object->getOwner()->getId();
      } elseif ($this->isBOAAllegato($object)) {

        return date('Y/m-d/Hi', $object->getCreatedAt()->format('U'));
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

  private function isUserAwareAllegato(Allegato $object)
  {
    return in_array($object->getType(), [
      'modulo_compilato',
      'allegato_operatore',
      'risposta_operatore',
      'allegato_scia',
      'richiesta_integrazione',
      'integrazione',
      'ritiro',
        'messaggio']
    );
  }

  /**
   * @todo Da rimuovere: fix per PAT - Domanda di bonus alimentare
   * @param Allegato $object
   * @return bool
   */
  private function isBOAAllegato(Allegato $object)
  {
    $pratiche = $object->getPratiche();
    foreach ($pratiche as $pratica){
      if ($pratica instanceof Pratica && $pratica->getServizio()->getSlug() == 'domanda-di-bonus-alimentare'){

        return true;
      }
    }

    return false;
  }
}
