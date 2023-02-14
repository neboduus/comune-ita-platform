<?php

namespace App\Model\Security\User;

use App\Model\Security\AbstractSecurityLog;
use App\Model\Security\SecurityLogInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class ResetPasswordRequestSecurityLog extends AbstractSecurityLog
{
  const SHORT_DESCRIPTION_TEMPLATE = "E' stato richiesto un cambio password per l'email %email% da %ip% (%city%, %country%)";

  protected string $action = SecurityLogInterface::ACTION_USER_RESET_PASSWORD_REQUEST;

  public function generateShortDescription(): void
  {
    $description = $this->addOriginToDescription(self::SHORT_DESCRIPTION_TEMPLATE);
    $placeholder['%email%'] = $this->subject['email'];
    $this->setShortDescription(strtr($description, $placeholder));
  }

  public function generateMeta(): void
  {
    $this->setMeta($this->subject);
  }


}
