<?php

namespace App\Model\Security\User;

use App\Entity\OperatoreUser;
use App\Model\Security\AbstractSecurityLog;
use App\Model\Security\SecurityLogInterface;

class OperatorRemovedSecurityLog extends AbstractSecurityLog
{
  const SHORT_DESCRIPTION_TEMPLATE = "E' stato eliminato un operatore da %ip% (%city%, %country%)";

  protected string $action = SecurityLogInterface::ACTION_USER_OPERATOR_REMOVED;

  public function generateShortDescription(): void
  {
    $description = $this->addOriginToDescription(self::SHORT_DESCRIPTION_TEMPLATE);
    $this->setShortDescription($description);
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
