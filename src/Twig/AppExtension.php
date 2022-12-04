<?php

namespace App\Twig;


use App\Utils\StringUtils;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class AppExtension  extends AbstractExtension
{

  public function getFunctions(): array
  {
    return [
      new TwigFunction('staticCall', [$this, 'staticCall']),
      new TwigFunction('generateInitialsAvatar', [$this, 'generateInitialsAvatar']),
    ];
  }

  /**
   * @return array|TwigFilter[]
   */
  public function getFilters(): array
  {
    return [
      new TwigFilter('cleanMarkup', [$this, 'cleanMarkup']),
      new TwigFilter('abstract', [$this, 'abstract']),
      new TwigFilter('isEmpty', [$this, 'isEmpty']),
    ];
  }

  /**
   * Clean markup.
   * @param string|null $string $string
   * @return string
   */
  public function cleanMarkup(?string $string): string
  {
    if ($string === null) {
      return '';
    }

    return StringUtils::cleanMarkup($string);
  }

  /**
   * Clean markup.
   * @param string|null $string $string
   * @return string
   */
  public function abstract(?string $string): ?string
  {
    return StringUtils::abstract($string);
  }

  /**
   * Check if summernote field is empty after cleaning
   *
   * @param string $string
   * @return boolean
   */
  public function isEmpty($string)
  {
    return strlen(strip_tags(trim($string))) == 0;
  }

  /**
   * Generate an initials avatar from the string passed
   * @param $string
   * @param string $size
   * @param string $color
   * @return string
   */
  public function generateInitialsAvatar(string $string, string $size = 'xs', string $color = ''): string
  {
    return StringUtils::generateInitialsAvatar($string, $size, $color);
  }

  /**
   * @param class-string $class
   * @param mixed        ...$args
   *
   * @return false|mixed
   *
   * @throws \Exception
   */
  public static function staticCall(string $class, string $method, ...$args)
  {
    if (!class_exists($class)) {
      throw new \Exception("Cannot call static method $method on Class $class: Invalid Class");
    }

    if (!method_exists($class, $method)) {
      throw new \Exception("Cannot call static method $method on Class $class: Invalid method");
    }

    return forward_static_call_array([$class, $method], $args);
  }
}
