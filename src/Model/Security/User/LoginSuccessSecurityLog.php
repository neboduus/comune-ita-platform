<?php

namespace App\Model\Security\User;

use App\Model\Security\AbstractSecurityLog;
use App\Model\Security\SecurityLogInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class LoginSuccessSecurityLog extends AbstractSecurityLog
{
  const SHORT_DESCRIPTION_TEMPLATE = '%username% ha fatto login con successo da %ip% (%city%, %country%)';

  protected string $action = SecurityLogInterface::ACTION_USER_LOGIN_SUCCESS;

  public function generateShortDescription(): void
  {
    $description = $this->addOriginToDescription(self::SHORT_DESCRIPTION_TEMPLATE);
    $placeholder = [];
    if ($this->user instanceof UserInterface) {
      $placeholder['%username%'] = $this->user->getUsername();
    }
    $this->setShortDescription(strtr($description, $placeholder));
  }

  public function generateMeta(): void
  {
    $this->setMeta(null);
  }


}
