<?php

namespace AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class AllegatoScia
 */

/**
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class AllegatoScia extends Allegato
{

  const TYPE_DEFAULT = 'allegato_scia';

  /**
   * AllegatoScia constructor.
   */
  public function __construct()
  {
    parent::__construct();
    $this->type = self::TYPE_DEFAULT;
  }

  public function getType(): string
  {
    return self::TYPE_DEFAULT;
  }
}
