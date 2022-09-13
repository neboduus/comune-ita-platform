<?php

namespace App\Model;

use JMS\Serializer\Annotation as Serializer;
use OpenApi\Annotations as OA;

class ParametersMetaPagedList
{
  /**
   * @var string
   * @Serializer\Type("intenger")
   * @OA\Property(description="Query offset of the list")
   */
  private $offset;

  /**
   * @var string
   * @Serializer\Type("intenger")
   * @OA\Property(description="Query limit of the list")
   */
  private $limit;

  /**
   * @return string
   */
  public function getOffset(): string
  {
    return $this->offset;
  }

  /**
   * @param string $offset
   */
  public function setOffset(string $offset): void
  {
    $this->offset = $offset;
  }

  /**
   * @return string
   */
  public function getLimit(): string
  {
    return $this->limit;
  }

  /**
   * @param string $limit
   */
  public function setLimit(string $limit): void
  {
    $this->limit = $limit;
  }




  /**
   * @return array|mixed
   */
  public function jsonSerialize()
  {
    return get_object_vars($this);
  }
}
