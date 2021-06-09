<?php

namespace AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class AdminUser
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks()
 * @package AppBundle\Entity
 */
class AdminUser extends User
{

  /**
   * @ORM\ManyToOne(targetEntity="Ente", inversedBy="administrators")
   * @ORM\JoinColumn(name="ente_id", referencedColumnName="id", nullable=true)
   */
  private $ente;

  /**
   * @var \DateTime
   *
   * @ORM\Column(name="last_change_password", type="datetime", nullable=true)
   */
  private $lastChangePassword;


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

  /**
   * @return \DateTime
   */
  public function getLastChangePassword()
  {
    return $this->lastChangePassword;
  }

  /**
   * @param \DateTime $lastChangePassword
   */
  public function setLastChangePassword(\DateTime $lastChangePassword)
  {
    $this->lastChangePassword = $lastChangePassword;
  }

}
