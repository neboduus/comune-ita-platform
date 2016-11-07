<?php

namespace AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
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
     * @ORM\ManyToMany(targetEntity="AppBundle\Entity\Pratica", mappedBy="moduliCompilati")
     * @var ArrayCollection $pratiche
     */
    private $pratiche;

    /**
     * ModuloCompilato constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->type = 'modulo_compilato';
    }

}
