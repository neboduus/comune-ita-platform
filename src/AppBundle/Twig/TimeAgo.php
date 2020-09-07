<?php

namespace AppBundle\Twig;


use Twig\TwigFilter;

class TimeAgo extends \Twig_Extension
{

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
      31536000 => 'anno',
      2592000 => 'mese',
      604800 => 'settimana',
      86400 => 'giorno',
      3600 => 'ora',
      60 => 'minuto',
      1 => 'secondo'
    );

    $plurali = array(
      'anno' => 'anni',
      'mese' => 'mesi',
      'settimana' => 'settimane',
      'giorno' => 'giorni',
      'ora' => 'ore',
      'minuto' => 'minuti',
      'secondo' => 'secondi'
    );

    foreach ($units as $unit => $val) {
      if ($time < $unit) continue;
      $numberOfUnits = floor($time / $unit);
      if ($val == 'secondo') {
        $timeAgo = 'pochi secondi fa';
      } else {
        if ($numberOfUnits > 1) {
          $timeAgo =  $numberOfUnits . ' ' . $plurali[$val] . ' fa';
        } else {
          $timeAgo =  $numberOfUnits . ' ' . $val . ' fa';
        }
      }
      return $timeAgo;
    }

  }

}
