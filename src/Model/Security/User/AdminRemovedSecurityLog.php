<?php

namespace App\Model\Security\User;

use App\Entity\AdminUser;
use App\Model\Security\AbstractSecurityLog;
use App\Model\Security\SecurityLogInterface;

class AdminRemovedSecurityLog extends AbstractSecurityLog
{
  const SHORT_DESCRIPTION_TEMPLATE = "E' stato eliminato un admin da %ip% (%city%, %country%)";

  protected string $action = SecurityLogInterface::ACTION_USER_ADMIN_REMOVED;

  public function generateShortDescription(): void
  {
    $description = $this->addOriginToDescription(self::SHORT_DESCRIPTION_TEMPLATE);
    $this->setShortDescription($description);
  }

  public function generateMeta(): void
  {
    if ($this->subject instanceof AdminUser) {
       $meta = [];
       $meta['id'] = $this->subject->getId();
       $meta['username'] = $this->subject->getUsername();
       $meta['email'] = $this->subject->getEmail();

       $this->setMeta($meta);
    }
  }


}
