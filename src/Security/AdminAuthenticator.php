<?php

namespace App\Security;

use App\Entity\AdminUser;

class AdminAuthenticator extends AppAuthenticator
{
    public const LOGIN_ROUTE = 'admin_login';

    public const TARGET_ROUTE = 'admin_index';

    protected function getUserClassName()
    {
        return AdminUser::class;
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
