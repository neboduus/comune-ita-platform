<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class AllegatoScia
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class AllegatoScia extends Allegato
{
    /**
     * AllegatoScia constructor.
     * @throws \Exception
     */
    public function __construct()
    {
        parent::__construct();
        $this->type = 'allegato_scia';
    }
}
