<?php


namespace AppBundle\Model;

use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;
use Swagger\Annotations as SWG;


class FlowStep implements FlowStepInterface, \JsonSerializable
{
  /**
   * @var string
   * @Serializer\Type("string")
   * @Assert\NotBlank(message="This field is mandatory: identifier")
   * @Assert\NotNull(message="This field is mandatory: identifier")
   * @SWG\Property(description="Human-readable unique identifiers")
   */
  private $identifier;

  /**
   * @var string
   * @Serializer\Type("string")
   * @Assert\NotBlank(message="This field is mandatory: title")
   * @Assert\NotNull(message="This field is mandatory: title")
   * @SWG\Property(description="Step's title")
   */
  private $title;

  /**
   * @var string
   * @Serializer\Type("string")
   * @Assert\NotBlank(message="This field is mandatory: type")
   * @Assert\NotNull(message="This field is mandatory: type")
   * @SWG\Property(description="Step's type, accepts values: formio")
   */
  private $type;

  /**
   * @var string
   * @Serializer\Type("string")
   * @SWG\Property(description="Step's description, accepts html tags")
   */
  private $description;

  /**
   * @var string
   * @Serializer\Type("string")
   * @SWG\Property(description="Compilation guide, accepts html tags")
   */
  private $guide;

  /**
   * @var array
   * @Serializer\Type("array<string, string>")
   * @SWG\Property(description="Specific parameters for flow step")
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
   * @return string
   */
  public function getTitle()
  {
    return $this->title;
  }

  /**
   * @param string $title
   */
  public function setTitle(string $title)
  {
    $this->title = $title;
  }

  /**
   * @return string
   */
  public function getDescription()
  {
    return $this->description;
  }

  /**
   * @param string $description
   */
  public function setDescription(string $description)
  {
    $this->description = $description;
  }

  /**
   * @return string
   */
  public function getGuide()
  {
    return $this->guide;
  }

  /**
   * @param string $guide
   */
  public function setGuide(string $guide)
  {
    $this->guide = $guide;
  }

  /**
   * @return string
   */
  public function getType()
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


  public function getParameters()
  {
    return $this->parameters;
  }

  /**
   * @param string $parameter
   * @return array|mixed|null
   */
  public function getParameter(string $parameter)
  {
    if (isset($this->parameters[$parameter])) {
      return $this->parameters[$parameter];
    }
    return null;
  }

  public function jsonSerialize()
  {
    return get_object_vars($this);
  }

}
