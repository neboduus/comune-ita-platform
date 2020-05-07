<?php

namespace App\DataFixtures\Main;

use App\Multitenancy\Listener\Command\FixturesListener;

trait MainFixturesTrait
{
    public static function getGroups(): array
    {
        return [FixturesListener::MAIN_GROUP];
    }
}
