<?php

namespace App\Entity;

use App\Repository\ContactPointRepository;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use JMS\Serializer\Annotation as Serializer;
use OpenApi\Annotations as OA;
use JMS\Serializer\Annotation\Groups;
use Gedmo\Mapping\Annotation as Gedmo;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass=ContactPointRepository::class)
 */
class ContactPoint
{
  /**
   * Hook timestampable behavior
   * updates createdAt, updatedAt fields
   */
  use TimestampableEntity;

  /**
   * @ORM\Id
   * @Serializer\Type("string")
   * @OA\Property(description="Contact Point's id")
   * @ORM\Column(type="guid")
   * @Groups({"read"})
   */
  private $id;

  /**
   * @Gedmo\Translatable
   * @ORM\Column(type="string", length=255)
   * @Serializer\Type("string")
   * @OA\Property(description="Contact Point's title")
   * @Groups({"read", "write"})
   */
  private $name;

  /**
   * @ORM\Column(type="string", length=255, nullable=true)
   * @Serializer\Type("string")
   * @OA\Property(description="Contact Point's email")
   * @Assert\Email()
   * @Groups({"read", "write"})
   */
  private $email;

  /**
   * @ORM\Column(type="string", length=255, nullable=true)
   * @Serializer\Type("string")
   * @OA\Property(description="Contact Point's url")
   * @Assert\Url()
   * @Groups({"read", "write"})
   */
  private $url;

  /**
   * @ORM\Column(type="string", length=255, nullable=true)
   * @Serializer\Type("string")
   * @OA\Property(description="Contact Point's phone number")
   * @Groups({"read", "write"})
   */
  private $phoneNumber;

  /**
   * @ORM\Column(type="string", length=255, nullable=true)
   * @Serializer\Type("string")
   * @OA\Property(description="Contact Point's pec")
   * @Assert\Email()
   * @Groups({"read", "write"})
   */
  private $pec;

  /**
   * @var DateTime
   * @Gedmo\Timestampable(on="create")
   * @ORM\Column(type="datetime")
   * @Groups({"read"})
   */
  protected $createdAt;

  /**
   * @var DateTime
   * @Gedmo\Timestampable(on="update")
   * @ORM\Column(type="datetime")
   * @Groups({"read"})
   */
  protected $updatedAt;

  public function __construct()
  {
    if (!$this->id) {
      $this->id = Uuid::uuid4();
    }
  }

  /**
   * @return mixed
   */
  public function getId()
  {
    return $this->id;
  }

  /**
   * @return string
   */
  public function getName(): ?string
  {
    return $this->name;
  }

  /**
   * @param string|null $name
   * @return ContactPoint
   */
  public function setName(?string $name): self
  {
    $this->name = $name;
    return $this;
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
   * @return ContactPoint
   */
  public function setEmail(?string $email): self
  {
    $this->email = $email;

    return $this;
  }

  /**
   * @return string
   */
  public function getUrl(): ?string
  {
    return $this->url;
  }

  /**
   * @param string|null $url
   * @return ContactPoint
   */
  public function setUrl(?string $url): self
  {
    $this->url = $url;

    return $this;
  }

  /**
   * @return string
   */
  public function getPhoneNumber(): ?string
  {
    return $this->phoneNumber;
  }

  /**
   * @param string|null $phoneNumber
   * @return ContactPoint
   */
  public function setPhoneNumber(?string $phoneNumber): self
  {
    $this->phoneNumber = $phoneNumber;

    return $this;
  }

  /**
   * @return string
   */
  public function getPec(): ?string
  {
    return $this->pec;
  }

  /**
   * @param string $pec
   */
  public function setPec(?string $pec): self
  {
    $this->pec = $pec;

    return $this;
  }

}
