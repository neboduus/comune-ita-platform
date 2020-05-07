<?php

namespace App\Repository;

use App\Entity\AdminUser;

class AdminRepository extends UserRepository
{
    protected function getUserClass()
    {
        return AdminUser::class;
    }
}
