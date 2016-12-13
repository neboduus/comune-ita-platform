<?php

namespace AppBundle\Twig;

use AppBundle\Entity\Pratica;
use Carbon\Carbon;


class IterCalculatorExtension extends \Twig_Extension
{

    private $durationStartStatus;

    private $durationEndStatus;

    public function __construct($durationStartStatus, $durationEndStatus)
    {
        $this->durationStartStatus = $durationStartStatus;
        $this->durationEndStatus = $durationEndStatus;
    }

    public function getName()
    {
        return 'iter_calculator_extension';
    }

    /**
     * @return array
     */
    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('iter_duration', array($this, 'getIterDuration')),
        );
    }

    /**
     * @param $pratica
     * @param null $locale
     *
     * @return string
     */
    public function getIterDuration($pratica, $locale = null)
    {
        if ($pratica instanceof Pratica) {
            $history = $pratica->getStoricoStati()->toArray();
            ksort($history);
            $startTimestamp = null;
            $endTimestamp = time();
            foreach ($history as $timestamp => $statuses) {
                foreach ($statuses as $status) {
                    if ($status[0] == $this->durationStartStatus) {
                        $startTimestamp = $timestamp;
                        break;
                    }elseif ($status[0] == $this->durationEndStatus) {
                        $endTimestamp = $timestamp;
                        break;
                    }
                }
            }

            if ($locale){
                Carbon::setLocale($locale);
            }

            if ($startTimestamp > 0) {
                $start = Carbon::instance(\DateTime::createFromFormat('U', $startTimestamp));
                $end = Carbon::instance(\DateTime::createFromFormat('U', $endTimestamp));

                return $start->diffForHumans($end, true);
            }
        }
        return '';
    }
}
