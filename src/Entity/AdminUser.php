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
   * AdminUser constructor.
   */
  public function __construct()
  {
    parent::__construct();
    $this->type = self::USER_TYPE_ADMIN;
    $this->addRole(User::ROLE_ADMIN);
  }


}
