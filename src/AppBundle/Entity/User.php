<?php

namespace AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Security\Core\User\UserInterface;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use FOS\UserBundle\Model\User as BaseUser;

/**
 * Class User
 *
 * @ORM\Entity
 * @ORM\Table(name="users")
 *
 * @package AppBundle\Entity
 */
class User extends BaseUser
{

    const FAKE_EMAIL_DOMAIN = 'cps.didnt.have.my.email.tld';

    /**
     * @ORM\Column(type="guid")
     * @ORM\Id
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     */
    private $name;

    /**
     * @var boolean
     *
     * @ORM\Column(name="terms_accepted", type="boolean")
     */
    private $termsAccepted = false;

    /**
     * User constructor.
     *
     */
    public function __construct()
    {
        parent::__construct();
        $this->id = Uuid::uuid4();
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param $name
     *
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    public function hasPassword()
    {
        return $this->password !== null;
    }

    /**
     * @return mixed
     */
    public function getTermsAccepted()
    {
        return $this->termsAccepted;
    }

    /**
     * @param $termsAccepted
     *
     * @return User
     */
    public function setTermsAccepted($termsAccepted)
    {
        $this->termsAccepted = $termsAccepted;

        return $this;
    }

}
