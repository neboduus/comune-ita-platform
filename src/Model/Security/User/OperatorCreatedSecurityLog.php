<?php

namespace App\Model\Security\User;

use App\Entity\OperatoreUser;
use App\Model\Security\AbstractSecurityLog;
use App\Model\Security\SecurityLogInterface;

class OperatorCreatedSecurityLog extends AbstractSecurityLog
{
  const SHORT_DESCRIPTION_TEMPLATE = "E' stato creato un nuovo operatore da %ip% (%city%, %country%)";

  protected string $action = SecurityLogInterface::ACTION_USER_OPERATOR_CREATED;

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
