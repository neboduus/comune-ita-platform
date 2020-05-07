<?php

namespace App\Model;

use JMS\Serializer\Annotation as Serializer;
use Swagger\Annotations as SWG;

class MetaPagedList
{
    /**
     * @var string
     * @Serializer\Type("string")
     * @SWG\Property(description="Total number of objects")
     */
    private $count;


    /**
     * @var array
     * @Serializer\Type("array<string, string>")
     * @SWG\Property(description="Specific parameters for flow step")
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
     * @return $this
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
