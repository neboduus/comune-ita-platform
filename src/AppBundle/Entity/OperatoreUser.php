<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class OperatoreUser
 * @ORM\Entity
 *
 * @package AppBundle\Entity
 */
class OperatoreUser extends User
{

    /**
     * CPSUser constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->type = self::USER_TYPE_OPERATORE;
    }
}