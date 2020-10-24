<?php


namespace App\Model;


use JMS\Serializer\Annotation as Serializer;
use JMS\Serializer\Annotation\Groups;
use Swagger\Annotations as SWG;
use Symfony\Component\Validator\Constraints as Assert;

class File
{

  /**
   * @var string
   * @Serializer\Type("string")
   * @SWG\Property(description="File's uuid")
   * @Groups({"read"})
   */
  private $id;

  /**
   * @var string
   * @Serializer\Type("string")
   * @Assert\NotBlank(message="This field is mandatory: name")
   * @Assert\NotNull(message="This field is mandatory: name")
   * @SWG\Property(description="Name of the file")
   * @Groups({"read", "write"})
   */
  private $name;

  /**
   * @var string
   * @Serializer\Type("string")
   * @Assert\NotBlank(message="This field is mandatory: mimeType")
   * @Assert\NotNull(message="This field is mandatory: mimeType")
   * @SWG\Property(description="MimeType of the file")
   * @Groups({"read", "write"})
   */
  private $mimeType;


  /**
   * @var string
   * @Serializer\Type("string")
   * @Assert\NotBlank(message="This field is mandatory: file")
   * @Assert\NotNull(message="This field is mandatory: file")
   * @SWG\Property(description="The content of the file in base64")
   * @Groups({"write"})
   */
  private $file;

  /**
   * @var string
   * @Serializer\Type("string")
   * @SWG\Property(description="Download url")
   * @Groups({"read"})
   */
  private $url;

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

}
