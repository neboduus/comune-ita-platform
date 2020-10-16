<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Class User
 *
 * @ORM\Entity
 * @ORM\Table(name="utente")
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="type", type="string")
 * @ORM\DiscriminatorMap({"operatore" = "OperatoreUser", "cps" = "CPSUser", "admin" = "AdminUser"})
 * @UniqueEntity(fields="usernameCanonical", errorPath="username", message="fos_user.username.already_used")
 * @ORM\AttributeOverrides({
 *      @ORM\AttributeOverride(name="email", column=@ORM\Column(type="string", name="email", length=255, unique=false, nullable=true)),
 *      @ORM\AttributeOverride(name="emailCanonical", column=@ORM\Column(type="string", name="email_canonical", length=255, unique=false, nullable=true))
 * })
 * @package App\Entity
 */
abstract class User implements UserInterface
{

    use TimestampableEntity;

    const ROLE_OPERATORE_ADMIN = 'ROLE_OPERATORE_ADMIN';
    const ROLE_OPERATORE = 'ROLE_OPERATORE';
    const ROLE_USER = 'ROLE_USER';
    const ROLE_ADMIN = 'ROLE_ADMIN';

    const USER_TYPE_OPERATORE = 'operatore';
    const USER_TYPE_CPS = 'cps';
    const USER_TYPE_ADMIN = 'admin';

    const FAKE_EMAIL_DOMAIN = 'cps.didnt.have.my.email.tld';

    /**
     * @var string
     *
     * @ORM\Column(name="cognome", type="string")
     */
    private $cognome;

    /**
     * @var string
     *
     * @ORM\Column(name="nome", type="string")
     */
    private $nome;

    /**
     * @ORM\Column(type="guid")
     * @ORM\Id
     */
    protected $id;

    protected $type;

    protected $fullName;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=true)
     */
    protected $emailContatto;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=true)
     */
    protected $cellulareContatto;

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
     * {@inheritDoc}
     */
    public function getId()
    {
        return (string) $this->id;
    }

    public function hasPassword()
    {
        return $this->password !== null;
    }

    /**
     * @return string
     */
    public function getCognome()
    {
        return $this->cognome;
    }

    /**
     * @param $cognome
     *
     * @return User
     */
    public function setCognome($cognome)
    {
        $this->cognome = $cognome;

        return $this;
    }

    /**
     * @return string
     */
    public function getNome()
    {
        return $this->nome;
    }

    /**
     * @param $nome
     *
     * @return User
     */
    public function setNome($nome)
    {
        $this->nome = $nome;

        return $this;
    }

    /**
     * @return string
     */
    public function getFullName()
    {
        if ($this->fullName == null){
            $this->fullName = $this->nome . ' ' . $this->cognome;
        }
        return $this->fullName;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getEmailContatto()
    {
        return $this->emailContatto;
    }

    /**
     * @param string $emailContatto
     *
     * @return $this
     */
    public function setEmailContatto($emailContatto)
    {
        $this->emailContatto = $emailContatto;

        return $this;
    }

    /**
     * @return string
     */
    public function getCellulareContatto()
    {
        return $this->cellulareContatto;
    }

    /**
     * @param string $cellulareContatto
     *
     * @return $this
     */
    public function setCellulareContatto($cellulareContatto)
    {
        $this->cellulareContatto = $cellulareContatto;

        return $this;
    }

  public function getRoles()
  {
    // TODO: Implement getRoles() method.
  }

  public function getPassword()
  {
    // TODO: Implement getPassword() method.
  }

  public function getSalt()
  {
    // TODO: Implement getSalt() method.
  }

  public function getUsername()
  {
    // TODO: Implement getUsername() method.
  }

  public function eraseCredentials()
  {
    // TODO: Implement eraseCredentials() method.
  }
}
