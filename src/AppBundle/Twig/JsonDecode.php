<?php

namespace AppBundle\Twig;


use Twig\TwigFilter;

class JsonDecode  extends \Twig_Extension
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