<?php

namespace App\Twig;


use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class JsonDecode  extends AbstractExtension
{

    public function getName()
    {
        return 'twig.json_decode';
    }

    public function getFilters()
    {
        return array(
            new TwigFilter('json_decode', array($this, 'jsonDecode'))
        );
    }

    public function jsonDecode($string)
    {
        return json_decode($string);
    }

}
