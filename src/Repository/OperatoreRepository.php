<?php

namespace App\Repository;

use App\Entity\OperatoreUser;

class OperatoreRepository extends UserRepository
{
    protected function getUserClass()
    {
        return OperatoreUser::class;
    }
}
