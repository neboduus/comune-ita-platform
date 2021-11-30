<?php

namespace AppBundle\Twig;


use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class AppExtension  extends AbstractExtension
{

  const ABSTRACT_LENGTH = 200;

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
      new TwigFilter('abstract', [$this, 'abstract']),
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

    $allowedMarkup = '<br><p><a><strong><ul><ol><i><b><u><li>';
    $string = strip_tags($string, $allowedMarkup);

    $string = preg_replace('/(<[^>]+) style=".*?"/i', '$1', $string);

    return $string;
  }

  /**
   * Clean markup.
   * @param string|null $string $string
   * @return string
   */
  public function abstract(?string $string): string
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
