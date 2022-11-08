<?php

namespace App\Model;

use DateTime;
use JMS\Serializer\Annotation as Serializer;
use JMS\Serializer\Annotation\Groups;
use OpenApi\Annotations as OA;
use Nelmio\ApiDocBundle\Annotation\Model;

class ServiceSource implements \JsonSerializable
{
  /**
   * @Serializer\Type("string")
   * @OA\Property(description="Service uuid")
   * @Groups({"read"})
   */
  private $id;

  /**
   * @Serializer\Type("string")
   * @OA\Property(description="Service url")
   * @Groups({"read"})
   */
  private $url;

  /**
   * @var DateTime
   * @Serializer\Type("DateTime")
   * @OA\Property(description="Updated at date time")
   * @Groups({"read"})
   */
  private $updatedAt;

  /**
   * @Serializer\Type("string")
   * @OA\Property(description="md5 of the imported service json response")
   * @Groups({"read"})
   */
  private $md5;

  /**
   * @Serializer\Type("string")
   * @OA\Property(description="Service version")
   */
  private $version;


  public function __construct($data = [])
  {
    if (!empty($data)) {
      $this->id = $data['id'] ?? null;
      $this->url = $data['url'] ?? null;
      try {
        $this->updatedAt = $data['updated_at'] ? new DateTime($data['updated_at']) : null;
      } catch (\Exception $e) {
        $this->updatedAt = null;
      }
      $this->md5 = $data['md5'] ?? null;
      $this->version = $data['version'] ?? null;
    }
  }

  /**
   * @return array|mixed
   */
  public function jsonSerialize()
  {
    return get_object_vars($this);
  }

  /**
   * Get the value of id
   */
  public function getId()
  {
    return $this->id;
  }

  /**
   * Set the value of id
   *
   * @return  self
   */
  public function setId($id)
  {
    $this->id = $id;

    return $this;
  }

  /**
   * Get the value of url
   */
  public function getUrl()
  {
    return $this->url;
  }

  /**
   * Set the value of url
   *
   * @return  self
   */
  public function setUrl($url)
  {
    $this->url = $url;

    return $this;
  }

  /**
   * Get the value of updatedAt
   *
   * @return  DateTime
   */
  public function getUpdatedAt(): ?DateTime
  {
    return $this->updatedAt;
  }

  /**
   * Set the value of updatedAt
   *
   * @param DateTime $updatedAt
   *
   * @return  self
   */
  public function setUpdatedAt(?DateTime $updatedAt)
  {
    $this->updatedAt = $updatedAt;

    return $this;
  }

  /**
   * Get the value of md5
   */
  public function getMd5()
  {
    return $this->md5;
  }

  /**
   * Set the value of md5
   *
   * @return  self
   */
  public function setMd5($md5)
  {
    $this->md5 = $md5;

    return $this;
  }

  /**
   * Get the value of version
   * @Serializer\SerializedName("version")
   */
  public function getVersion()
  {
    return $this->version;
  }

  /**
   * Set the value of version
   *
   * @return  self
   */
  public function setVersion($version)
  {
    $this->version = $version;

    return $this;
  }
}
