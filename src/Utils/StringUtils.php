<?php

namespace App\Utils;

class StringUtils
{

  /**
   * @param string $string
   * @return string|string[]|null
   */
  public static function clean(string $string)
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

  /**
   * @param $filename
   * @return string
   */
  public static function sanitizeFileName($filename ): string
  {

    $filename = mb_convert_encoding($filename, "ASCII", "auto");

    $special_chars = array( '?', '[', ']', '/', '\\', '=', '<', '>', ':', ';', ',', "'", '"', '&', '$', '#', '*', '(', ')', '|', '~', '`', '!', '{', '}', '%', '+', '’', '«', '»', '”', '“', chr( 0 ) );

    // Check for support for utf8 in the installed PCRE library once and store the result in a static.
    static $utf8_pcre = null;
    if ( ! isset( $utf8_pcre ) ) {
      $utf8_pcre = @preg_match( '/^./u', 'a' );
    }

    if ( $utf8_pcre ) {
      $filename = preg_replace( "#\x{00a0}#siu", ' ', $filename );
    }

    $filename = str_replace( $special_chars, '', $filename );
    $filename = str_replace( array( '%20', '+' ), '-', $filename );
    $filename = preg_replace( '/[\r\n\t -]+/', '-', $filename );
    $filename = trim( $filename, '.-_' );


    return strtolower($filename);
  }

  /**
   * @return string
   */
  public static function randomPassword(): string
  {
    $alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
    $pass = array(); //remember to declare $pass as an array
    $alphaLength = strlen($alphabet) - 1; //put the length -1 in cache
    for ($i = 0; $i < 8; $i++) {
      $n = rand(0, $alphaLength);
      $pass[] = $alphabet[$n];
    }
    return implode($pass); //turn the array into a string
  }

}
