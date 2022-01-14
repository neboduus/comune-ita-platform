<?php

namespace AppBundle\Utils;

class StringUtils
{

  /**
   * @param $string
   * @return string|string[]|null
   */
  public static function clean($string)
  {
    $string = str_replace(['/', '\\', ' '], '-', $string); // Replaces all spaces with hyphens.

    return preg_replace('/[^A-Za-z0-9\-\.]/', '', $string); // Removes special chars.
  }

  /**
   * @param $string
   * @return string|string[]|null
   */
  public static function cleanMarkup($string)
  {
    $allowedMarkup = '<br><p><a><strong><ul><ol><i><b><u><li>';
    $string = strip_tags($string, $allowedMarkup);

    $string = preg_replace('/(<[^>]+) style=".*?"/i', '$1', $string);

    return $string;
  }

}
