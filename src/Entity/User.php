<?php

namespace App\Entity;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
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
 * @UniqueEntity(fields="usernameCanonical", errorPath="username", message="This value is already used.")
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
   * @ORM\Column(type="guid")
   * @ORM\Id
   */
  protected $id;

  /**
   * @var string
   * @ORM\Column(type="string", length=180, unique=true)
   */
  protected $username;

  /**
   * @var string
   * @ORM\Column(type="string", length=180)
   */
  protected $usernameCanonical;

  /**
   * @var string
   * @ORM\Column(type="string", name="email", length=255, unique=false, nullable=true)
   */
  protected $email;

  /**
   * @var string
   * @ORM\Column(type="string", name="email_canonical", length=255, unique=false, nullable=true)
   */
  protected $emailCanonical;

  /**
   * @ORM\Column(name="enabled", type="boolean")
   */
  protected $enabled;

  /**
   * The salt to use for hashing.
   *
   * @var string
   * @ORM\Column(type="string", length=255)
   */
  protected $salt;

  /**
   * Encrypted password. Must be persisted.
   *
   * @var string
   * @ORM\Column(type="string", length=255)
   */
  protected $password;

  /**
   * Plain password. Used for model validation. Must not be persisted.
   *
   * @var string
   */
  protected $plainPassword;

  /**
   * @var \DateTime
   */
  protected $lastLogin;

  /**
   * Random string sent to the user email address in order to verify it.
   *
   * @var string
   * @ORM\Column(type="string", length=180)
   */
  protected $confirmationToken;

  /**
   * @var \DateTime
   */
  protected $passwordRequestedAt;

  /**
   * @var \DateTime
   *
   * @ORM\Column(name="last_change_password", type="datetime", nullable=true)
   */
  protected $lastChangePassword;

  /**
   * @ORM\Column(type="array")
   */
  protected $roles;

  /**
   * @var string
   * @ORM\Column(name="nome", type="string")
   */
  private $nome;

  /**
   * @var string
   * @ORM\Column(name="cognome", type="string")
   */
  private $cognome;

  /** @var string */
  protected $type;

  /** @var string */
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
    $this->id = Uuid::uuid4();
    $this->enabled = false;
    $this->roles = array();
  }

  /**
   * @param $role
   * @return $this
   */
  public function addRole($role)
  {
    $role = strtoupper($role);
    if ($role === self::ROLE_USER) {
      return $this;
    }

    if (!in_array($role, $this->roles, true)) {
      $this->roles[] = $role;
    }

    return $this;
  }

  /**
   * @return array
   */
  public function getRoles()
  {
    $roles = $this->roles;
    // guarantee every user at least has ROLE_USER
    $roles[] = self::ROLE_USER;

    return array_unique($roles);
  }

  /**
   * @param $role
   * @return bool
   */
  public function hasRole($role)
  {
    return in_array(strtoupper($role), $this->getRoles(), true);
  }

  /**
   * @param $role
   * @return $this
   */
  public function removeRole($role)
  {
    if (false !== $key = array_search(strtoupper($role), $this->roles, true)) {
      unset($this->roles[$key]);
      $this->roles = array_values($this->roles);
    }

    return $this;
  }

  /**
   * @param array $roles
   * @return $this
   */
  public function setRoles(array $roles)
  {
    $this->roles = array();

    foreach ($roles as $role) {
      $this->addRole($role);
    }

    return $this;
  }

  /**
   * @return UuidInterface
   */
  public function getId()
  {
    return $this->id;
  }

  /**
   * @param UuidInterface $id
   */
  public function setId(UuidInterface $id): void
  {
    $this->id = $id;
  }



  /**
   * @return string
   */
  public function getUsernameCanonical(): string
  {
    return $this->usernameCanonical;
  }

  /**
   * @param string $usernameCanonical
   */
  public function setUsernameCanonical(string $usernameCanonical): void
  {
    $this->usernameCanonical = $usernameCanonical;
  }

  /**
   * @return string
   */
  public function getEmailCanonical(): string
  {
    return $this->emailCanonical;
  }

  /**
   * @param string $emailCanonical
   */
  public function setEmailCanonical(string $emailCanonical): void
  {
    $this->emailCanonical = $emailCanonical;
  }

  /**
   * @return bool
   */
  public function isEnabled(): bool
  {
    return $this->enabled;
  }

  /**
   * @param bool $enabled
   */
  public function setEnabled(bool $enabled)
  {
    $this->enabled = $enabled;
    return $this;
  }

  /**
   * @return string
   */
  public function getPlainPassword(): string
  {
    return $this->plainPassword;
  }

  /**
   * @param string $plainPassword
   */
  public function setPlainPassword(string $plainPassword): void
  {
    $this->plainPassword = $plainPassword;
  }

  /**
   * @return \DateTime
   */
  public function getLastLogin(): \DateTime
  {
    return $this->lastLogin;
  }

  /**
   * @param \DateTime $lastLogin
   */
  public function setLastLogin(\DateTime $lastLogin): void
  {
    $this->lastLogin = $lastLogin;
  }

  /**
   * @return string
   */
  public function getConfirmationToken(): string
  {
    return $this->confirmationToken;
  }

  /**
   * @param string $confirmationToken
   */
  public function setConfirmationToken(?string $confirmationToken)
  {
    $this->confirmationToken = $confirmationToken;
    return $this;
  }

  /**
   * @return \DateTime
   */
  public function getPasswordRequestedAt(): \DateTime
  {
    return $this->passwordRequestedAt;
  }

  /**
   * @param \DateTime $passwordRequestedAt
   * @return User
   */
  public function setPasswordRequestedAt(\DateTime $passwordRequestedAt)
  {
    $this->passwordRequestedAt = $passwordRequestedAt;
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
  public function setLastChangePassword(\DateTime $lastChangePassword = null)
  {
    if ($lastChangePassword == null ){
      $lastChangePassword = new \DateTime();
    }
    $this->lastChangePassword = $lastChangePassword;
  }


  public function hasPassword()
  {
    return $this->password !== null;
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
  public function getFullName()
  {
    if ($this->fullName == null) {
      $this->fullName = $this->nome.' '.$this->cognome;
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

  /**
   * @see UserInterface
   */
  public function getPassword()
  {
    return (string)$this->password;
  }

  public function setPassword(string $password): self
  {
    $this->password = $password;

    return $this;
  }

  /**
   * @return string
   */
  public function getUsername()
  {
    return $this->username;
  }

  /**
   * @param string $username
   */
  public function setUsername(string $username)
  {
    $this->username = $username;
    $this->usernameCanonical = $username;
    return $this;
  }

  /**
   * @return string
   */
  public function getEmail()
  {
    return $this->email;
  }

  /**
   * @param string $email
   */
  public function setEmail(string $email)
  {
    $this->email = $email;
    return $this;
  }

  public function eraseCredentials()
  {
    $this->plainPassword = null;
  }

  /**
   * @see UserInterface
   */
  public function getSalt()
  {
    // not needed when using the "bcrypt" algorithm in security.yaml
  }
}
