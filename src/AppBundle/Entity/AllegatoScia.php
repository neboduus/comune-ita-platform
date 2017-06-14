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

    /**
     * ModuloCompilato constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->type = 'allegato_scia';
    }
}
