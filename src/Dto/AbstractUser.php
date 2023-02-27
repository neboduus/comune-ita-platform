<?php


namespace App\Dto;

use JMS\Serializer\Annotation as Serializer;
use OpenApi\Annotations as OA;
use JMS\Serializer\Annotation\Groups;

abstract class AbstractUser
{

  /**
   * @Serializer\Type("string")
   * @OA\Property(description="User's uuid", type="string", format="uuid")
   * @Groups({"read"})
   */
  protected $id;

  /**
   * @var string
   *
   * @Serializer\Type("string")
   * @OA\Property(description="Operator's role", type="string", default="user")
   * @Groups({"read"})
   */
  protected string $role;


  /**
   * @var string
   *
   * @Serializer\Type("string")
   * @OA\Property(description="User's name")
   * @Groups({"read", "write"})
   */
  protected $nome;

  /**
   * @var string
   *
   * @Serializer\Type("string")
   * @OA\Property(description="User's surname")
   * @Groups({"read", "write"})
   */
  protected $cognome;

  /**
   * @var string
   *
   * @Serializer\Type("string")
   * @OA\Property(description="Operator's fullname")
   * @Groups({"read"})
   */
  protected string $fullName;

  /**
   * @var string
   *
   * @Serializer\Type("string")
   * @OA\Property(description="User's mobile phone")
   * @Groups({"read", "write"})
   */
  protected $cellulare;

  /**
   * @var string
   *
   * @Serializer\Type("string")
   * @OA\Property(description="User's email", type="string", format="email")
   * @Groups({"read", "write"})
   */
  protected $email;

  /**
   * @return mixed
   */
  public function getId()
  {
    return $this->id;
  }

  /**
   * @param mixed $id
   */
  public function setId($id)
  {
    $this->id = $id;
  }

  /**
   * @return string
   */
  public function getRole(): string
  {
    return $this->role;
  }

  /**
   * @param string $role
   */
  public function setRole(string $role)
  {
    $this->role = $role;
  }

  /**
   * @return string
   */
  public function getNome()
  {
    return $this->nome;
  }

  /**
   * @param string $nome
   */
  public function setNome($nome)
  {
    $this->nome = $nome;
  }

  /**
   * @return string
   */
  public function getCognome()
  {
    return $this->cognome;
  }

  /**
   * @param string $cognome
   */
  public function setCognome($cognome)
  {
    $this->cognome = $cognome;
  }

  /**
   * @return string
   */
  public function getFullName(): string
  {
    return $this->fullName;
  }

  /**
   * @param string $fullName
   */
  public function setFullName(string $fullName)
  {
    $this->fullName = $fullName;
  }

  /**
   * @return string
   */
  public function getCellulare(): ?string
  {
    return $this->cellulare;
  }

  /**
   * @param string|null $cellulare
   */
  public function setCellulare(?string $cellulare): void
  {
    $this->cellulare = $cellulare;
  }

  /**
   * @return string
   */
  public function getEmail(): ?string
  {
    return $this->email;
  }

  /**
   * @param string|null $email
   */
  public function setEmail(?string $email): void
  {
    $this->email = $email;
  }
}
