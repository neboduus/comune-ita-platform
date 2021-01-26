<?php


namespace AppBundle\Model;


use JMS\Serializer\Annotation as Serializer;
use Swagger\Annotations as SWG;
use Symfony\Component\Validator\Constraints as Assert;

class IOServiceParameters
{
  // {"service_id":"","primary_key":"","secondary_key":""}

  /**
   * @var string
   * @Serializer\Type("string")
   * @Assert\NotBlank(message="This field is mandatory: service_id")
   * @Assert\NotNull(message="This field is mandatory: service_id")
   * @SWG\Property(description="IO Service id")
   */
  private $IOserviceId;

  /**
   * @var string
   * @Serializer\Type("string")
   * @Assert\NotBlank(message="This field is mandatory: primary_key")
   * @Assert\NotNull(message="This field is mandatory: primary_key")
   * @SWG\Property(description="IO Service primary key")
   */
  private $primaryKey;

  /**
   * @var string
   * @Serializer\Type("string")
   * @Assert\NotBlank(message="This field is mandatory: secondary_key")
   * @Assert\NotNull(message="This field is mandatory: secondary_key")
   * @SWG\Property(description="IO Service secondary key")
   */
  private $secondaryKey;

  /**
   * @return string
   */
  public function getIOServiceId(): ?string
  {
    return $this->IOserviceId;
  }

  /**
   * @param string $serviceId
   */
  public function setIOServiceId(string $serviceId)
  {
    $this->IOserviceId = $serviceId;
  }

  /**
   * @return string
   */
  public function getPrimaryKey(): ?string
  {
    return $this->primaryKey;
  }

  /**
   * @param string $primaryKey
   */
  public function setPrimaryKey(string $primaryKey)
  {
    $this->primaryKey = $primaryKey;
  }

  /**
   * @return string
   */
  public function getSecondaryKey(): ?string
  {
    return $this->secondaryKey;
  }

  /**
   * @param string $secondaryKey
   */
  public function setSecondaryKey(string $secondaryKey)
  {
    $this->secondaryKey = $secondaryKey;
  }

}