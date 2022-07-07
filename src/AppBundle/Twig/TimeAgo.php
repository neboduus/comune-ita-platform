<?php

namespace AppBundle\Twig;


use Symfony\Component\Translation\TranslatorInterface;
use Twig\TwigFilter;



class TimeAgo extends \Twig_Extension
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
      'anno' => $this->translator->trans('time.years'),
      'mese' => $this->translator->trans('time.months'),
      'settimana' => $this->translator->trans('time.weeks'),
      'giorno' => $this->translator->trans('time.days'),
      'ora' => $this->translator->trans('time.hours'),
      'minuto' => $this->translator->trans('time.minutes'),
      'secondo' => $this->translator->trans('time.seconds'),
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
