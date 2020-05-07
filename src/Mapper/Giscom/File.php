<?php

namespace App\Mapper\Giscom;

use App\Mapper\HashableInterface;

class File implements HashableInterface
{
    private $id;

    private $name;

    private $type;

    private $protocollo;

    private $content;

    public function __construct(array $file = null)
    {
        if (is_array($file)) {
            foreach ($file as $key => $value) {
                if (property_exists($this, $key)) {
                    $this->{$key} = $value;
                }
            }
        }
    }

    public function hasContent()
    {
        return $this->id !== null;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     *
     * @return File
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     *
     * @return File
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param mixed $type
     *
     * @return File
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @param mixed $content
     *
     * @return File
     */
    public function setContent($content)
    {
        $this->content = $content;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getProtocollo()
    {
        return $this->protocollo;
    }

    /**
     * @param mixed $protocollo
     */
    public function setProtocollo($protocollo)
    {
        $this->protocollo = $protocollo;
    }

    public function toHash()
    {
        $objectArray = [];
        foreach ($this as $key => $value) {
            $objectArray[$key] = $value;
        }

        return $objectArray;
    }
}
