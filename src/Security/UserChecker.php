<?php

namespace App\Security;

use App\Entity\User;
use App\Exception\AccountDisabledException;
use App\Exception\AccountNotInTenantException;
use App\Services\TenantService;
use Symfony\Component\Security\Core\Exception\AccountExpiredException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserChecker implements UserCheckerInterface
{

  public function checkPreAuth(UserInterface $user)
  {

    if (!$user instanceof User) {
      return;
    }

    // User is deleted, show a generic Account Not Found message.
    //if ($user->isDeleted()) {
    //     throw new AccountDeletedException();
    //}

    // user is disabled, show a generic Account Not Found message.
    if (!$user->isEnabled()) {
      throw new \Exception('User is disabled');
    }
  }

  public function checkPostAuth(UserInterface $user)
  {
    if (!$user instanceof User) {
      return;
    }

    // user account is expired, the user may be notified
    //if ($user->isExpired()) {
    //  throw new AccountExpiredException();
    //}
  }
}
