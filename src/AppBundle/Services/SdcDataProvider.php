<?php

namespace AppBundle\Services;


use Symfony\Component\HttpFoundation\ParameterBag;

class SdcDataProvider extends ParameterBag
{
    public function __construct(array $parameters)
    {
        parent::__construct($parameters);
    }

    public function __call($name, $arguments)
    {
        if ($this->has($name)){
            return $this->get($name);
        }
        return null;
    }

}
