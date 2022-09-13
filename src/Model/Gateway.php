<?php


namespace App\Model;

use Doctrine\Common\Collections\ArrayCollection;
use InvalidArgumentException;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Annotations as OA;

class Gateway implements \JsonSerializable
{
  /**
   * @var string
   * @Serializer\Type("string")
   * @Assert\NotBlank(message="This field is mandatory: identifier")
   * @Assert\NotNull(message="This field is mandatory: identifier")
   * @OA\Property(description="Human-readable unique identifiers")
   */
  private $identifier;

  /**
   * @var array
   * @Serializer\Type("array<string, string>")
   * @OA\Property(description="Specific parameters for gateways")
   *
   */
  private $parameters = array();

  /**
   * @return string
   */
  public function getIdentifier()
  {
    return $this->identifier;
  }

  /**
   * @param string $identifier
   */
  public function setIdentifier(string $identifier)
  {
    $this->identifier = $identifier;
  }

  /**
   * @return array
   */
  public function getParameters()
  {
    return $this->parameters;
  }

  /**
   * @param array
   */
  public function setParameters($parameters)
  {
    if (!is_array($parameters)) {
      $parameters = json_decode($parameters, true);
    }
    $this->parameters = $parameters;
  }


  public function jsonSerialize()
  {
    return get_object_vars($this);
  }
}
