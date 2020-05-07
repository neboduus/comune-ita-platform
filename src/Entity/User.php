<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Class User
 *
 * @ORM\Entity(repositoryClass="App\Repository\UserRepository")
 * @ORM\Table(name="utente")
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="type", type="string")
 * @ORM\DiscriminatorMap({"operatore" = "OperatoreUser", "cps" = "CPSUser", "admin" = "AdminUser"})
 * @UniqueEntity(fields="usernameCanonical", errorPath="username", message="Username already used")
 * @package App\Entity
 */
abstract class User implements UserInterface
{
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

    /**
     * @var string
     * @ORM\Column(name="username", type="string", length=180)
     */
    protected $username;

    /**
     * @var string
     * @ORM\Column(name="username_canonical", type="string", length=180, unique=true)
     */
    protected $usernameCanonical;

    /**
     * @var string
     * @ORM\Column(name="email", type="string", length=255, unique=false, nullable=true)
     */
    protected $email;

    /**
     * @var string
     * @ORM\Column(name="email_canonical", type="string", length=255, unique=false, nullable=true)
     */
    protected $emailCanonical;

    /**
     * @var bool
     * @ORM\Column(name="enabled", type="boolean")
     */
    protected $enabled;

    /**
     * The salt to use for hashing.
     *
     * @var string
     * @ORM\Column(name="salt", type="string", nullable=true)
     */
    protected $salt;

    /**
     * Encrypted password. Must be persisted.
     *
     * @var string
     * @ORM\Column(type="string")
     */
    protected $password;

    /**
     * Plain password. Used for model validation. Must not be persisted.
     *
     * @var string
     */
    protected $plainPassword;

    /**
     * @var \DateTime|null
     * @ORM\Column(name="last_login", type="datetime", nullable=true)
     */
    protected $lastLogin;

    /**
     * Random string sent to the user email address in order to verify it.
     *
     * @var string|null
     * @ORM\Column(name="confirmation_token", type="string", length=180, nullable=true)
     */
    protected $confirmationToken;

    /**
     * @var \DateTime|null
     * @ORM\Column(name="password_requested_at", type="datetime", nullable=true)
     */
    protected $passwordRequestedAt;

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
     * @var array
     * @ORM\Column(type="json")
     */
    protected $roles;

    /**
     * User constructor.
     *
     */
    public function __construct()
    {
        $this->enabled = false;
        $this->roles = array();
        $this->id = Uuid::uuid4();
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return (string) $this->getUsername();
    }

    /**
     * {@inheritDoc}
     */
    public function getId()
    {
        return (string) $this->id;
    }

    /**
     * @return string
     */
    public function getUsername(): ?string
    {
        return $this->username;
    }

    /**
     * @param string $username
     * @return User
     */
    public function setUsername(string $username): User
    {
        $this->username = $username;
        return $this;
    }

    /**
     * @return string
     */
    public function getUsernameCanonical(): ?string
    {
        return $this->usernameCanonical;
    }

    /**
     * @param string $usernameCanonical
     * @return User
     */
    public function setUsernameCanonical(string $usernameCanonical): User
    {
        $this->usernameCanonical = $usernameCanonical;
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
     * @return User
     */
    public function setEmail(string $email)
    {
        $this->email = $email;
        return $this;
    }

    /**
     * @return string
     */
    public function getEmailCanonical(): ?string
    {
        return $this->emailCanonical;
    }

    /**
     * @param string $emailCanonical
     * @return User
     */
    public function setEmailCanonical(string $emailCanonical): User
    {
        $this->emailCanonical = $emailCanonical;
        return $this;
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
     * @return User
     */
    public function setEnabled(bool $enabled): User
    {
        $this->enabled = $enabled;
        return $this;
    }

    public function getSalt()
    {
        // not needed when using the "bcrypt" algorithm in security.yaml
    }

    /**
     * @return string
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    /**
     * @param string $password
     * @return User
     */
    public function setPassword(string $password): User
    {
        $this->password = $password;
        return $this;
    }

    /**
     * @return string
     */
    public function getPlainPassword()
    {
        return $this->plainPassword;
    }

    /**
     * @param string $plainPassword
     * @return User
     */
    public function setPlainPassword(string $plainPassword): User
    {
        $this->plainPassword = $plainPassword;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getConfirmationToken(): ?string
    {
        return $this->confirmationToken;
    }

    /**
     * @param string|null $confirmationToken
     * @return User
     */
    public function setConfirmationToken(?string $confirmationToken): User
    {
        $this->confirmationToken = $confirmationToken;
        return $this;
    }

    /**
     * @return \DateTime|null
     */
    public function getLastLogin(): ?\DateTime
    {
        return $this->lastLogin;
    }

    /**
     * @param \DateTime|null $lastLogin
     * @return User
     */
    public function setLastLogin(?\DateTime $lastLogin): User
    {
        $this->lastLogin = $lastLogin;
        return $this;
    }

    /**
     * @return \DateTime|null
     */
    public function getPasswordRequestedAt(): ?\DateTime
    {
        return $this->passwordRequestedAt;
    }

    /**
     * @param \DateTime|null $passwordRequestedAt
     * @return User
     */
    public function setPasswordRequestedAt(?\DateTime $passwordRequestedAt): User
    {
        $this->passwordRequestedAt = $passwordRequestedAt;
        return $this;
    }

    /**
     * @return array
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param array $roles
     * @return User
     */
    public function setRoles(array $roles): User
    {
        $this->roles = $roles;
        return $this;
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
        if ($this->fullName == null) {
            $this->fullName = $this->cognome . ' ' . $this->nome;
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

    public function eraseCredentials()
    {
        $this->plainPassword = null;
    }

    public function addRole($role)
    {
        $role = strtoupper($role);
        if ($role === static::ROLE_USER) {
            return $this;
        }

        if (!in_array($role, $this->roles, true)) {
            $this->roles[] = $role;
        }

        return $this;
    }

    public function removeRole($role)
    {
        if (false !== $key = array_search(strtoupper($role), $this->roles, true)) {
            unset($this->roles[$key]);
            $this->roles = array_values($this->roles);
        }

        return $this;
    }
}
