<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class AdminUser
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks()
 * @package App\Entity
 */
class AdminUser extends User
{

  /**
   * @ORM\ManyToOne(targetEntity="Ente", inversedBy="administrators")
   * @ORM\JoinColumn(name="ente_id", referencedColumnName="id", nullable=true)
   */
  private $ente;


  /**
   * AdminUser constructor.
   */
  public function __construct()
  {
    parent::__construct();
    $this->type = self::USER_TYPE_ADMIN;
    $this->addRole(User::ROLE_ADMIN);
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
   * @return AdminUser
   */
  public function setEnte(Ente $ente)
  {
    $this->ente = $ente;

    return $this;
  }

}
