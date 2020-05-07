<?php

namespace App\Mapper\Giscom\SciaPraticaEdilizia;

use App\Mapper\Giscom\FileCollection;
use App\Mapper\HashableInterface;

abstract class AbstractSciaPraticaEdiliziaMappable implements HashableInterface
{
    protected $tipo;

    public function __construct($data, $tipo = null)
    {
        $this->tipo = $tipo;
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                if (in_array($key, $this->getProperties())) {
                    $this->{$key} = new FileCollection($value);
                }
            }
        }
    }

    /**
     * @param $name
     *
     * @return FileCollection
     */
    public function __get($name)
    {
        if (property_exists($this, $name)) {
            if ($this->{$name} == null) {
                return new FileCollection();
            }

            return $this->{$name};
        } else {
            throw new \InvalidArgumentException("Property $name does not exists in class " . self::class);
        }
    }

    public function __set($name, $value)
    {
        if (in_array($name, $this->getProperties())) {
            $this->{$name} = $value instanceof FileCollection ? $value : new FileCollection();
        }
    }

    public function toHash()
    {
        $objectArray = [];
        foreach ($this->getProperties() as $key) {
            if (isset($this->{$key}) && $this->{$key} instanceof HashableInterface) {
                $objectArray[$key] = $this->{$key}->toHash();
            } else {
                $objectArray[$key] = [];
            }
        }

        return $objectArray;
    }

    public function getAllegatiIdArray()
    {
        $idArray = [];
        foreach ($this as $key => $value) {
            if ($value instanceof FileCollection) {
                $idArray = array_merge_recursive($idArray, $value->toIdArray());
            }
        }

        return $idArray;
    }

    /**
     * @param string $property
     * @param array $requiredProperties
     *
     * @return bool
     */
    public function isRequired($field, $requiredFields = [])
    {
        return in_array($field, $requiredFields);
    }

    /**
     * @return string[]
     */
    abstract public function getProperties();

    /**
     * @param string $tipoIntervento
     *
     * @return array
     */
    abstract public function getRequiredFields($tipoIntervento);
}
