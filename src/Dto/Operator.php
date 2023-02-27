<?php


namespace App\Dto;

use App\Entity\OperatoreUser;
use JMS\Serializer\Annotation as Serializer;
use OpenApi\Annotations as OA;
use JMS\Serializer\Annotation\Groups;

class Operator extends AbstractUser
{
  const USER_TYPE_OPERATORE = 'operator';

  /**
   * @var string
   *
   * @Serializer\Type("string")
   * @OA\Property(description="Operator's username")
   * @Groups({"read", "write"})
   */
  private string $username;

  /**
   * @var array
   *
   * @Serializer\Type("array")
   * @OA\Property(description="Operator's enabled services", type="array", @OA\Items(type="string", format="uuid"))
   * @Groups({"read"})
   */
  private array $enabledServicesIds = [];

  /**
   * @var array
   *
   * @Serializer\Type("array")
   * @OA\Property(description="Operator's user groups", type="array", @OA\Items(type="string", format="uuid"))
   * @Groups({"read"})
   */
  private array $userGroupsIds = [];

  /**
   * @var bool
   * @Serializer\Type("bool")
   * @OA\Property(description="API operator", type="bool")
   * @Groups({"read"})
   */
  private bool $systemUser = false;

  /**
   * @return string
   */
  public function getUsername(): string
  {
    return $this->username;
  }

  /**
   * @param string $username
   */
  public function setUsername(string $username)
  {
    $this->username = $username;
  }

  /**
   * @return array
   */
  public function getEnabledServicesIds(): array
  {
    return $this->enabledServicesIds;
  }

  /**
   * @param array $enabledServicesIds
   */
  public function setEnabledServicesIds(array $enabledServicesIds): void
  {
    $this->enabledServicesIds = $enabledServicesIds;
  }

  /**
   * @return array
   */
  public function getUserGroupsIds(): array
  {
    return $this->userGroupsIds;
  }

  /**
   * @param array $userGroupsIds
   */
  public function setUserGroupsIds(array $userGroupsIds): void
  {
    $this->userGroupsIds = $userGroupsIds;
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
   */
  public function setSystemUser(bool $systemUser): void
  {
    $this->systemUser = $systemUser;
  }

  /**
   * @param OperatoreUser $operatoreUser
   * @return Operator
   */
  public static function fromEntity(OperatoreUser $operatoreUser): Operator
  {
    $dto = new self();
    $dto->id = $operatoreUser->getId();
    $dto->nome = $operatoreUser->getNome();
    $dto->cognome = $operatoreUser->getCognome();
    $dto->fullName = $operatoreUser->getFullName();
    $dto->cellulare = $operatoreUser->getCellulareContatto();
    $dto->email = $operatoreUser->getEmail();
    $dto->username = $operatoreUser->getUsername();
    $dto->role = self::USER_TYPE_OPERATORE;
    $dto->enabledServicesIds = $operatoreUser->getServiziAbilitati()->toArray();
    $dto->systemUser = $operatoreUser->isSystemUser();

    $userGroupsIds = [];
    if ($operatoreUser->getUserGroups()) {
      foreach ($operatoreUser->getUserGroups() as $u) {
        $userGroupsIds[] = $u->getId();
      }
    }
    $dto->userGroupsIds = $userGroupsIds;

    return $dto;
  }

  /**
   * @param OperatoreUser|null $entity
   * @return OperatoreUser
   */
  public function toEntity(OperatoreUser $entity = null): ?OperatoreUser
  {
    if (!$entity) {
      $entity = new OperatoreUser();
    }
    $entity->setUsername($this->username);
    $entity->setNome($this->nome);
    $entity->setCognome($this->cognome);

    $entity->setCellulareContatto($this->cellulare ?? '');
    $entity->setEmail($this->email);
    $entity->setSystemUser($this->systemUser);

    return $entity;
  }
}
