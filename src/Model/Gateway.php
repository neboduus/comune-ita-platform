<?php


namespace App\Model;

use JMS\Serializer\Annotation as Serializer;
use Swagger\Annotations as SWG;
use Symfony\Component\Validator\Constraints as Assert;

class Gateway implements \JsonSerializable
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
     * @var array
     * @Serializer\Type("array<string, string>")
     * @SWG\Property(description="Specific parameters for gateways")
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
