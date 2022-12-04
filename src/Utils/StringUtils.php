<?php

namespace App\Utils;

class StringUtils
{

  const ABSTRACT_LENGTH = 160;

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
    $allowedMarkup = '<br><p><a><strong><ul><ol><i><b><u><li><h3><h4><h5><h6>';
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


  /**
   * @param array $data
   * @return array
   */
  public static function cleanData(array $data): array
  {
    $dataString = str_replace("\\u0000", "",  json_encode($data));
    $dataString = str_replace("\\x00", "",  $dataString);
    return (array)json_decode($dataString, true);
  }

  /**
   * @param string|null $string
   * @return mixed|string
   */
  // Todo: Trovare un nome migliore se possibile
  public static function abstract(?string $string)
  {
    $abstract = '';
    if ($string === null) {
      return $abstract;
    }

    $string = strip_tags($string, '<p>');
    $string = preg_replace("/<([a-z][a-z0-9]*)[^>]*?(\/?)>/si",'<$1$2>', $string);
    $string = html_entity_decode($string);

    if (preg_match('/<p>(.*?)<\/p>/i', $string, $paragraphs)) {
      $abstract = $paragraphs[1];
    } else {
      $abstract = $string;
    }

    if (strlen($abstract) > self::ABSTRACT_LENGTH) {
      $abstract = \mb_substr($abstract, 0, self::ABSTRACT_LENGTH);
      $abstractParts = explode(' ', $abstract);
      array_pop($abstractParts);
      $abstract = implode(' ', $abstractParts) . '...';
    }

    return $abstract;
  }

  public static function shortenDescription(?string $string): ?string
  {
    $abstract = '';
    if ($string === null) {
      return $abstract;
    }
    $abstract = strip_tags($string);
    if (strlen($abstract) > self::ABSTRACT_LENGTH) {
      $abstract = \mb_substr($abstract, 0, self::ABSTRACT_LENGTH);
    }
    return $abstract;
  }
  /**
   * @param int $bytes
   * @return string
   */
  public static function getHumanReadableFilesize(int $bytes): string
  {
    $bytes = floatval($bytes);

    $arBytes = array(
      0 => array(
        "UNIT" => "TB",
        "VALUE" => pow(1024, 4),
      ),
      1 => array(
        "UNIT" => "GB",
        "VALUE" => pow(1024, 3),
      ),
      2 => array(
        "UNIT" => "MB",
        "VALUE" => pow(1024, 2),
      ),
      3 => array(
        "UNIT" => "KB",
        "VALUE" => 1024,
      ),
      4 => array(
        "UNIT" => "B",
        "VALUE" => 1,
      ),
    );

    $result = '';
    foreach ($arBytes as $arItem) {
      if ($bytes >= $arItem["VALUE"]) {
        $result = $bytes / $arItem["VALUE"];
        $result = str_replace(".", ",", strval(round($result, 2)))." ".$arItem["UNIT"];
        break;
      }
    }

    return $result;
  }

  public static function generateInitialsAvatar(string $string, string $size = 'xs', string $color = ''): string
  {
    $availableColors = ['', 'primary', 'secondary', 'green', 'orange', 'red'];

    $words = \explode(' ', $string);
    $words = array_filter($words, function($word) {
      return $word!=='' && $word !== ',';
    });

    if(!$words) {
      return '';
    }

    // get second letter
    $secondLetter = isset($words[1]) ? mb_strtoupper(trim(mb_substr($words[1], 0, 1, 'UTF-8'))) : '';
    // get first letter
    $firstLetter = mb_strtoupper(trim(mb_substr($words[0], 0, 1, 'UTF-8')));

    if (!in_array($color, $availableColors)) {
      $color = $availableColors[array_rand($availableColors, 1)];
    }

    return '<div class="avatar avatar-'.$color.' size-'.$size.'"><p aria-hidden="true">'. $firstLetter . $secondLetter .'</p><span class="sr-only">'. $string .'</span></div>';
  }
}
