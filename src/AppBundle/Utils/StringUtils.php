<?php

namespace AppBundle\Utils;

class StringUtils
{

  public static function clean($string) {
    $string = str_replace(['/', '\\', ' '], '-', $string); // Replaces all spaces with hyphens.

    return preg_replace('/[^A-Za-z0-9\-]/', '', $string); // Removes special chars.
  }

}
