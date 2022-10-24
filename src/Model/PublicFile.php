<?php


namespace App\Model;

use App\Utils\StringUtils;
use Craue\FormFlowBundle\Util\StringUtil;
use DateTime;
use DateTimeInterface;
use JMS\Serializer\Annotation as Serializer;


class PublicFile implements \JsonSerializable
{
  const CONDITIONS_TYPE = 'conditions';
  const COSTS_TYPE = 'costs';

  /**
   * @var string
   * @Serializer\Type("string")
   */
  private $name;

  /**
   * @var string
   * @Serializer\Type("string")
   */
  private $originalName;

  /**
   * @var int
   * @Serializer\Type("int")
   */
  private $size;

  /**
   * @var string
   * @Serializer\Type("string")
   */
  private $mimeType;

  /**
   * @var string
   * @Serializer\Type("string")
   */
  private $type;

  /**
   * @var DateTime
   * @Serializer\Type("DateTime")
   */
  private $createdAt;

  /**
   * Public file constructor.
   */
  public function __construct()
  {
    $this->setCreatedAt(new DateTime());
  }
  /**
   * @return string
   */
  public function getName(): string
  {
    return $this->name;
  }

  /**
   * @param string $name
   */
  public function setName(string $name)
  {
    $this->name = $name;
  }

  /**
   * @return string
   */
  public function getOriginalName(): string
  {
    return $this->originalName;
  }

  /**
   * @param string $originalName
   */
  public function setOriginalName(string $originalName)
  {
    $this->originalName = $originalName;
  }

  /**
   * @return int
   */
  public function getSize(): int
  {
    return $this->size;
  }

  /**
   * @param int $size
   */
  public function setSize(int $size)
  {
    $this->size = $size;
  }

  /**
   * @return string
   */
  public function getMimeType(): string
  {
    return $this->mimeType;
  }

  /**
   * @param string $mimeType
   */
  public function setMimeType(string $mimeType)
  {
    $this->mimeType = $mimeType;
  }

  /**
   * @return string
   */
  public function getType(): string
  {
    return $this->type;
  }

  /**
   * @param string $type
   */
  public function setType(string $type)
  {
    $this->type = $type;
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
  public function isConditions(): bool
  {
    return $this->type === self::CONDITIONS_TYPE;
  }

  /**
   * @return bool
   */
  public function isCosts(): bool
  {
    return $this->type === self::COSTS_TYPE;
  }

  /**
   * @return string
   */
  public function getDecoratedName(): string
  {
    return pathinfo($this->originalName, PATHINFO_FILENAME) . " (" . strtoupper(pathinfo($this->name, PATHINFO_EXTENSION)) . " " . StringUtils::getHumanReadableFilesize($this->size) . ")";
  }

  public function jsonSerialize(): array
  {
    return array(
      'name' => $this->name,
      'original_name'=> $this->originalName,
      'size' => $this->size,
      'mime_type'  => $this->mimeType,
      'type' => $this->type,
      'created_at' => $this->createdAt->format(DateTimeInterface::ATOM),
    );
  }

}
