<?php

namespace AppBundle\Utils;

class ConfigUtils
{

  public static function arrayMergeRecursiveDistinct(array &$array1, array &$array2)
  {
    $merged = $array1;

    foreach ($array2 as $key => &$value) {
      if (is_array($value) && isset ($merged [$key]) && is_array($merged [$key])) {
        $merged [$key] = self::arrayMergeRecursiveDistinct($merged [$key], $value);
      } else {
        $merged [$key] = $value;
      }
    }

    return $merged;
  }

}
