<?php

namespace AppBundle\Twig;


use Twig\TwigFilter;
use Twig\Extension\AbstractExtension;

class Unserialize  extends AbstractExtension
{

    public function getName()
    {
        return 'twig.unserialize';
    }

    public function getFilters()
    {
        return array(
            new TwigFilter('unserialize', array($this, 'unserialize'))
        );
    }

    public function unserialize($string)
    {
        return unserialize($string);
    }

}
