<?php

namespace App\Twig;


use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;



class TimeAgo extends AbstractExtension
{
  /**
   * @var TranslatorInterface
   */
  private $translator;

  public function __construct(TranslatorInterface $translator)
  {
    $this->translator = $translator;
  }

  public function getName()
  {
    return 'twig.time_ago';
  }

  public function getFilters()
  {
    return array(
      new TwigFilter('time_ago', array($this, 'timeAgo'))
    );
  }

  public function timeAgo($_time)
  {

    $time = time() - $_time;

    $units = array(
      31536000 => $this->translator->trans('time.year'),
      2592000 => $this->translator->trans('time.month'),
      604800 => $this->translator->trans('time.week'),
      86400 => $this->translator->trans('time.day'),
      3600 => $this->translator->trans('time.hour'),
      60 => $this->translator->trans('time.minute'),
      1 => $this->translator->trans('time.second')
    );

    $plurali = array(
      $this->translator->trans('time.year') => $this->translator->trans('time.years'),
      $this->translator->trans('time.month') => $this->translator->trans('time.months'),
      $this->translator->trans('time.week') => $this->translator->trans('time.weeks'),
      $this->translator->trans('time.day') => $this->translator->trans('time.days'),
      $this->translator->trans('time.hour') => $this->translator->trans('time.hours'),
      $this->translator->trans('time.minute') => $this->translator->trans('time.minutes'),
      $this->translator->trans('time.second') => $this->translator->trans('time.seconds'),
    );

    foreach ($units as $unit => $val) {
      if ($time < $unit) continue;
      $numberOfUnits = floor($time / $unit);
      if ($val == $this->translator->trans('time.second')) {
        $timeAgo = $this->translator->trans('time.few_seconds_ago');
      } else {
        if ($numberOfUnits > 1) {
          $timeAgo =  $numberOfUnits . ' ' . $plurali[$val] . ' ' .$this->translator->trans('time.ago');
        } else {
          $timeAgo =  $numberOfUnits . ' ' . $val . ' ' .$this->translator->trans('time.ago');
        }
      }
      return $timeAgo;
    }
  }

}
