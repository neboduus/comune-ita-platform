<?php


namespace App\Dto;

use App\Entity\AdminUser;
use JMS\Serializer\Annotation as Serializer;
use OpenApi\Annotations as OA;
use JMS\Serializer\Annotation\Groups;

class Admin extends AbstractUser
{
  const USER_TYPE_ADMIN = 'admin';

  /**
   * @var string
   *
   * @Serializer\Type("string")
   * @OA\Property(description="Admin's username")
   * @Groups({"read", "write"})
   */
  private string $username;

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
   * @param AdminUser $adminUser
   * @return Admin
   */
  public static function fromEntity(AdminUser $adminUser): Admin
  {
    $dto = new self();
    $dto->id = $adminUser->getId();
    $dto->nome = $adminUser->getNome();
    $dto->cognome = $adminUser->getCognome();
    $dto->fullName = $adminUser->getFullName();
    $dto->cellulare = $adminUser->getCellulareContatto();
    $dto->email = $adminUser->getEmail();
    $dto->username = $adminUser->getUsername();
    $dto->role = self::USER_TYPE_ADMIN;

    return $dto;
  }

  /**
   * @param AdminUser|null $entity
   * @return AdminUser
   */
  public function toEntity(AdminUser $entity = null): ?AdminUser
  {
    if (!$entity) {
      $entity = new AdminUser();
    }
    $entity->setUsername($this->username);
    $entity->setNome($this->nome);
    $entity->setCognome($this->cognome);

    $entity->setCellulareContatto($this->cellulare);
    $entity->setEmail($this->email);

    return $entity;
  }
}
