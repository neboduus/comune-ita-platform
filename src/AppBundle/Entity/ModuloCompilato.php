<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class ModuloCompilato
 */
/**
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class ModuloCompilato extends Allegato
{

    /**
     * ModuloCompilato constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->type = 'modulo_compilato';
    }

}
