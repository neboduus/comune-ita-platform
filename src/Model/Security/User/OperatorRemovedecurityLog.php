<?php

namespace App\Model\Security\User;

use App\Entity\OperatoreUser;
use App\Model\Security\AbstractSecurityLog;
use App\Model\Security\SecurityLogInterface;

class OperatorRemovedecurityLog extends AbstractSecurityLog
{
  const SHORT_DESCRIPTION_TEMPLATE = "E' stato eliminato un operatore da %ip% (%city%, %country%)";

  protected string $action = SecurityLogInterface::ACTION_USER_OPERATOR_REMOVED;

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
    if ($this->subject instanceof OperatoreUser) {
       $meta = [];
       $meta['id'] = $this->subject->getId();
       $meta['username'] = $this->subject->getUsername();
       $meta['email'] = $this->subject->getEmail();

       $this->setMeta($meta);
    }
  }


}
