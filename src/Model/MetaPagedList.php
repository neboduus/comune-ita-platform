<?php

namespace App\Model;

use JMS\Serializer\Annotation as Serializer;
use OpenApi\Annotations as OA;

class MetaPagedList
{
  /**
   * @var string
   * @Serializer\Type("string")
   * @OA\Property(description="Total number of objects")
   */
  private $count;


  /**
   * @var array
   * @Serializer\Type("array<string, string>")
   * @OA\Property(description="Specific parameters for flow step")
   *
   */
  private $parameter = array();

  /**
   * @return string
   */
  public function getCount(): string
  {
    return $this->count;
  }

  /**
   * @param string $count
   */
  public function setCount(string $count): void
  {
    $this->count = $count;
  }


  /**
   * @return array
   */
  public function getParameter()
  {
    return $this->parameter;
  }

  /**
   * @param $parameter
   * @return array
   */
  public function setParameter($parameter)
  {
    $this->parameter = $parameter;
    return $this;
  }

  /**
   * @return array|mixed
   */
  public function jsonSerialize()
  {
    return get_object_vars($this);
  }
}
