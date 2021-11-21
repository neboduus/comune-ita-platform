<?php

namespace AppBundle\Twig;


use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class AppExtension  extends AbstractExtension
{
  public function getFunctions(): array
  {
    return [
      new TwigFunction('staticCall', [$this, 'staticCall']),
    ];
  }

  /**
   * @return array|TwigFilter[]
   */
  public function getFilters(): array
  {
    return [
      new TwigFilter('cleanMarkup', [$this, 'cleanMarkup']),
    ];
  }

  /**
   * Clean markup.
   * @param string $string
   * @return string
   */
  public function cleanMarkup(string $string): string
  {
    $allowedMarkup = '<br><p><a><strong><ul><ol><i><b><u><li>';
    $string = strip_tags($string, $allowedMarkup);

    $string = preg_replace('/(<[^>]+) style=".*?"/i', '$1', $string);

    return $string;
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
