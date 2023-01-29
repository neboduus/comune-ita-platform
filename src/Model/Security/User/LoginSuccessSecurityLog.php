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
    $placeholder = [];

    if ($this->user instanceof UserInterface) {
      $placeholder['%username%'] = $this->user->getUsername();
    }

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
