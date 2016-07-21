<?php

namespace spec\Opencontent\SDC\Entities;

use Opencontent\SDC\Entities\Cittadino;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class CittadinoSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType(Cittadino::class);
    }
}
