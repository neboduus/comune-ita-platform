<?php

namespace AppBundle\Entity;

use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Class User
 *
 * @package AppBundle\Entity
 */
class User implements UserInterface
{
    /**
     * @var string
     */
    protected $name;

    private $username;
    private $password;
    private $salt;
    private $roles = array();

    /**
     * User constructor.
     *
     */
    public function __construct()
    {
        $this->roles []= 'ROLE_USER';
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    public function getRoles()
    {
        return $this->roles;
    }

    public function getPassword()
    {
        return $this->password;
    }

    public function getSalt()
    {
        return $this->salt;
    }

    public function getUsername()
    {
        return $this->username;
    }

    public function eraseCredentials()
    {
        return null;
    }
}
