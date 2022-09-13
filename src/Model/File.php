<?php


namespace App\Model;


use DateTime;
use JMS\Serializer\Annotation as Serializer;
use JMS\Serializer\Annotation\Groups;
use OpenApi\Annotations as OA;
use Symfony\Component\Validator\Constraints as Assert;

class File
{

  /**
   * @var string
   * @Serializer\Type("string")
   * @OA\Property(description="File's uuid")
   * @Groups({"read"})
   */
  private $id;

  /**
   * @var string
   * @Serializer\Type("string")
   * @Assert\NotBlank(message="This field is mandatory: name")
   * @Assert\NotNull(message="This field is mandatory: name")
   * @OA\Property(description="Name of the file")
   * @Groups({"read", "write"})
   */
  private $name;

  /**
   * @var string
   * @Serializer\Type("string")
   * @Assert\NotBlank(message="This field is mandatory: mimeType")
   * @Assert\NotNull(message="This field is mandatory: mimeType")
   * @OA\Property(description="MimeType of the file")
   * @Groups({"read", "write"})
   */
  private $mimeType;


  /**
   * @var string
   * @Serializer\Type("string")
   * @Assert\NotBlank(message="This field is mandatory: file")
   * @Assert\NotNull(message="This field is mandatory: file")
   * @OA\Property(description="The content of the file in base64")
   * @Groups({"write"})
   */
  private $file;

  /**
   * @var string
   * @Serializer\Type("string")
   * @OA\Property(description="Download url")
   * @Groups({"read"})
   */
  private $url;

  /**
   * @var string
   * @Serializer\Type("string")
   * @OA\Property(description="Original file name")
   * @Groups({"read"})
   */
  private $originalName;

  /**
   * @var string
   * @Serializer\Type("string")
   * @OA\Property(description="Description")
   * @Groups({"read"})
   */
  private $description;

  /**
   * @var DateTime
   * @Serializer\Type("DateTime")
   * @OA\Property(description="Created datetime")
   * @Groups({"read"})
   */
  private $createdAt;

  /**
   * @var boolean
   * @Serializer\Type("bool")
   * @OA\Property(description="Protocol required")
   * @Groups({"read"})
   */
  private $protocolRequired;

  /**
   * @var string
   * @Serializer\Type("string")
   * @OA\Property(description="Protocol number")
   * @Groups({"read"})
   */
  private $protocolNumber;

  /**
   * @return string
   */
  public function getId(): ?string
  {
    return $this->id;
  }

  /**
   * @param string $id
   */
  public function setId(string $id): void
  {
    $this->id = $id;
  }

  /**
   * @return string
   */
  public function getName(): ?string
  {
    return $this->name;
  }

  /**
   * @param string $name
   */
  public function setName(string $name): void
  {
    $this->name = $name;
  }

  /**
   * @return string
   */
  public function getMimeType(): ?string
  {
    return $this->mimeType;
  }

  /**
   * @param string $mimeType
   */
  public function setMimeType(string $mimeType): void
  {
    $this->mimeType = $mimeType;
  }

  /**
   * @return string
   */
  public function getFile(): ?string
  {
    return $this->file;
  }

  /**
   * @param string $file
   */
  public function setFile(string $file): void
  {
    $this->file = $file;
  }

  /**
   * @return string
   */
  public function getUrl(): ?string
  {
    return $this->url;
  }

  /**
   * @param string $url
   */
  public function setUrl(string $url): void
  {
    $this->url = $url;
  }

  /**
   * @return string
   */
  public function getOriginalName(): ?string
  {
    return $this->originalName;
  }

  /**
   * @param string $originalName
   */
  public function setOriginalName(?string $originalName): void
  {
    $this->originalName = $originalName;
  }

  /**
   * @return string
   */
  public function getDescription(): ?string
  {
    return $this->description;
  }

  /**
   * @param string $description
   */
  public function setDescription(?string $description): void
  {
    $this->description = $description;
  }

  /**
   * @return DateTime
   */
  public function getCreatedAt(): ?DateTime
  {
    return $this->createdAt;
  }

  /**
   * @param DateTime $createdAt
   */
  public function setCreatedAt(DateTime $createdAt): void
  {
    $this->createdAt = $createdAt;
  }

  /**
   * @return bool
   */
  public function isProtocolRequired(): ?bool
  {
    return $this->protocolRequired;
  }

  /**
   * @param bool $protocolRequired
   */
  public function setProtocolRequired(?bool $protocolRequired): void
  {
    $this->protocolRequired = $protocolRequired;
  }

  /**
   * @return string
   */
  public function getProtocolNumber(): ?string
  {
    return $this->protocolNumber;
  }

  /**
   * @param string $protocolNumber
   */
  public function setProtocolNumber(?string $protocolNumber): void
  {
    $this->protocolNumber = $protocolNumber;
  }

}
