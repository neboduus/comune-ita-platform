<?php

namespace App\Twig;


use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class HtmlSpecialCharsDecode extends AbstractExtension
{

    public function getName()
    {
        return 'twig.htmlspecialchars_decode';
    }

    public function getFilters()
    {
        return array(
            new TwigFilter('htmlspecialchars_decode', array($this, 'htmlSpecialCharsDecode'))
        );
    }

    public function htmlSpecialCharsDecode($string)
    {
        return htmlspecialchars_decode($string);
    }

}
