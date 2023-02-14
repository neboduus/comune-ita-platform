<?php

namespace App\Model\Security\User;

use App\Model\Security\AbstractSecurityLog;
use App\Model\Security\SecurityLogInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class LoginFailedSecurityLog extends AbstractSecurityLog
{
  const SHORT_DESCRIPTION_TEMPLATE = 'Login fallito da %ip% (%city%, %country%)';

  protected string $action = SecurityLogInterface::ACTION_USER_LOGIN_FAILED;

  public function generateShortDescription(): void
  {
    $description = $this->addOriginToDescription(self::SHORT_DESCRIPTION_TEMPLATE);
    $this->setShortDescription($description);
  }

  public function generateMeta(): void
  {
    $this->setMeta(null);
  }


}
