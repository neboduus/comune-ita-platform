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
