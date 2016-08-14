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
     * @ORM\ManyToOne(targetEntity="Ente")
     * @ORM\JoinColumn(name="ente_id", referencedColumnName="id", nullable=true)
     */
    private $ente;

    /**
     * CPSUser constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->type = self::USER_TYPE_OPERATORE;
    }

    /**
     * @return mixed
     */
    public function getEnte()
    {
        return $this->ente;
    }

    /**
     * @param Ente $ente
     * @return OperatoreUser
     */
    public function setEnte(Ente $ente)
    {
        $this->ente = $ente;

        return $this;
    }
}
