<?php

namespace App\Security;

use App\Entity\OperatoreUser;

class OperatoreAuthenticator extends AppAuthenticator
{
    public const LOGIN_ROUTE = 'operatori_login';

    public const TARGET_ROUTE = 'operatori_index';

    protected function getUserClassName()
    {
        return OperatoreUser::class;
    }

    protected function getLoginRoute()
    {
        return self::LOGIN_ROUTE;
    }

    protected function getDefaultTargetRoute()
    {
        return self::TARGET_ROUTE;
    }

}
