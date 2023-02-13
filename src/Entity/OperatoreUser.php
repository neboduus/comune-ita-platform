<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;

/**
 * Class OperatoreUser
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks()
 * @package App\Entity
 */
class OperatoreUser extends User
{

  /**
   * @ORM\ManyToOne(targetEntity="Ente", inversedBy="operatori")
   * @ORM\JoinColumn(name="ente_id", referencedColumnName="id", nullable=true)
   */
  private $ente;

  /**
   * @var string
   *
   * @ORM\Column(name="ambito", type="string")
   */
  private $ambito;

  /**
   * @var Collection
   *
   * @ORM\Column(name="servizi_abilitati", type="text")
   */
  private $serviziAbilitati;

  /**
   * @ORM\OneToMany(targetEntity="App\Entity\UserGroup", mappedBy="manager")
   * @var ArrayCollection
   * @Serializer\Exclude()
   */
  private $userGroupsManager;

  /**
   * @ORM\ManyToMany(targetEntity="App\Entity\UserGroup", mappedBy="users")
   * @var ArrayCollection
   * @Serializer\Exclude()
   */
  private $userGroups;

  /**
   * @var bool
   * @ORM\Column(type="boolean", nullable=true, options={"default" : 0})
   */
  private $systemUser = false;

  /**
   * OperatoreUser constructor.
   */
  public function __construct()
  {
    parent::__construct();
    $this->type = self::USER_TYPE_OPERATORE;
    $this->addRole(User::ROLE_OPERATORE);
    $this->serviziAbilitati = new ArrayCollection();
    $this->userGroups = new ArrayCollection();
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
   * @return OperatoreUser
   */
  public function setEnte(Ente $ente)
  {
    $this->ente = $ente;

    return $this;
  }

  /**
   * @return string
   */
  public function getAmbito()
  {
    return $this->ambito;
  }

  /**
   * @param string $ambito
   */
  public function setAmbito($ambito)
  {
    $this->ambito = $ambito;
  }

  /**
   * @return ArrayCollection
   */
  public function getUserGroupsManager(): ArrayCollection
  {
    return $this->userGroupsManager;
  }

  /**
   * @param ArrayCollection $userGroupsManager
   */
  public function setUserGroupsManager(ArrayCollection $userGroupsManager): void
  {
    $this->userGroupsManager = $userGroupsManager;
  }

  /**
   * @return Collection
   */
  public function getServiziAbilitati(): Collection
  {
    if (!($this->serviziAbilitati instanceof Collection)) {
      $this->serviziAbilitati = new ArrayCollection(json_decode($this->serviziAbilitati));
    }

    return $this->serviziAbilitati;
  }

  /**
   * @param Collection $servizi
   * @return $this
   */
  public function setServiziAbilitati(Collection $servizi)
  {
    $this->serviziAbilitati = $servizi;

    return $this;
  }

  /**
   * @ORM\PostLoad()
   * @ORM\PostUpdate()
   */
  public function parseServizi()
  {
    if (!($this->serviziAbilitati instanceof Collection)) {
      $this->serviziAbilitati = new ArrayCollection(json_decode($this->serviziAbilitati));
    }
  }

  /**
   * @ORM\PreFlush()
   */
  public function serializeServizi()
  {
    if ($this->serviziAbilitati instanceof Collection) {
      $this->serviziAbilitati = json_encode($this->getServiziAbilitati()->toArray());
    }
  }

  /**
   * @return Collection<int, UserGroup>
   */
  public function getUserGroups(): Collection
  {
    return $this->userGroups;
  }

  public function addUserGroup(UserGroup $userGroup): self
  {
    if (!$this->userGroups->contains($userGroup)) {
      $this->userGroups[] = $userGroup;
      $userGroup->addUser($this);
    }

    return $this;
  }

  public function removeUserGroup(UserGroup $userGroup): self
  {
    if ($this->userGroups->removeElement($userGroup)) {
      $userGroup->removeUser($this);
    }

    return $this;
  }

  /**
   * @return bool
   */
  public function isSystemUser(): ?bool
  {
    return $this->systemUser;
  }

  /**
   * @param bool $systemUser
   * @return $this
   */
  public function setSystemUser(bool $systemUser): OperatoreUser
  {
    $this->systemUser = $systemUser;
    return $this;
  }
}
