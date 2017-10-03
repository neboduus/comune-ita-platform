<?php

namespace AppBundle\Mapper;


interface HashableInterface
{
    /**
     * @return []
     */
    public function toHash();
}
