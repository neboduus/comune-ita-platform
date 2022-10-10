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
   * @Groups({"read"})
   */
  private $version;


  public function __construct($id, $url, $updatedAt, $md5, $version)
  {
    $this->id = $id;
    $this->url = $url;
    $this->updatedAt = $updatedAt;
    $this->md5 = $md5;
    $this->version = $version;
  }

  /**
   * @return array|mixed
   */
  public function jsonSerialize()
  {
    $object_to_serialize = get_object_vars($this);
    $object_to_serialize['updated_at'] = $object_to_serialize['updatedAt'];
    unset($object_to_serialize['updatedAt']);
    return $object_to_serialize;
  }
}
