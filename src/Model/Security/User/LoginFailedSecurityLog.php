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
    $placeholder = [];

    if ($this->origin) {
      if ($this->origin->getIp()) {
        $placeholder['%ip%'] = $this->origin->getIp();
      }

      if ($this->origin->getCity()) {
        $placeholder['%city%'] = $this->origin->getCity();
      }

      if ($this->origin->getCountry()) {
        $placeholder['%country%'] = $this->origin->getCountry();
      }
    }

    $this->setShortDescription(strtr(self::SHORT_DESCRIPTION_TEMPLATE, $placeholder));
  }

  public function generateMeta(): void
  {
    $this->setMeta(null);
  }


}
