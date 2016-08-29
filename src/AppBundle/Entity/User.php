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
 * @ORM\Table(name="utente")
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="type", type="string")
 * @ORM\DiscriminatorMap({"operatore" = "OperatoreUser", "cps" = "CPSUser"})
 *
 * @package AppBundle\Entity
 */
abstract class User extends BaseUser
{

    const USER_TYPE_OPERATORE = 'operatore';

    const USER_TYPE_CPS = 'cps';

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
     * @return $this
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
     * @return $this
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
     * @return User
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
     * @return User
     */
    public function setCellulareContatto($cellulareContatto)
    {
        $this->cellulareContatto = $cellulareContatto;

        return $this;
    }


}
