<?php

namespace App\Mapper;


interface HashableInterface
{
    /**
     * @return []
     */
    public function toHash();
}
