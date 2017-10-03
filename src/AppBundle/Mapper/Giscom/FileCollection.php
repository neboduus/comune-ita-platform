<?php

namespace AppBundle\Mapper\Giscom;

use AppBundle\Mapper\HashableInterface;

class FileCollection extends \ArrayObject implements HashableInterface
{
    public function __construct($input = array())
    {
        $files = array();
        if (is_array($input)){
            foreach($input as $file){
                $files[] = $file instanceof File ? $file : new File($file);
            }
        }
        parent::__construct($files);
    }

    public function toHash()
    {
        $objectArray = [];
        foreach($this as $key => $value) {
            if ($value instanceof HashableInterface){
                $objectArray[$key] = $value->toHash();
            }else{
                $objectArray[$key] = (array)$value;
            }
        }

        return $objectArray;
    }

    public function toIdArray()
    {
        $idList = [];
        foreach($this as $file){
            $idList[] = $file->getId();
        }

        return $idList;
    }

}
