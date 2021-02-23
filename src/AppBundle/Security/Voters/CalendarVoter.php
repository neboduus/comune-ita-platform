<?php


namespace AppBundle\Security\Voters;


use AppBundle\Entity\Calendar;
use AppBundle\Entity\OperatoreUser;
use AppBundle\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Security;

class CalendarVoter extends Voter
{
  const EDIT = 'edit';
  const DELETE = 'delete';


  private $security;

  public function __construct(Security $security)
  {
    $this->security = $security;
  }

  protected function supports($attribute, $subject)
  {
    // if the attribute isn't one we support, return false
    if (!in_array($attribute, [self::EDIT, self::DELETE])) {
      return false;
    }

    // only vote on `Calendar` objects
    if ($subject && !$subject instanceof Calendar) {
      return false;
    }

    return true;
  }

  protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
  {
    $user = $token->getUser();

    if (!$user instanceof User) {
      // the user must be logged in; if not, deny access
      return false;
    }

    // you know $subject is a Calendar object, thanks to `supports()`
    /** @var Calendar $calendar */
    $calendar = $subject;

    switch ($attribute) {
      case self::EDIT:
        return $this->canEdit($calendar, $user);
      case self::DELETE:
        return $this->canDelete($calendar, $user);

    }

    throw new \LogicException('This code should not be reached!');
  }

  private function canEdit(Calendar $calendar, User $user)
  {
    if ($this->security->isGranted('ROLE_ADMIN')) {
      return true;
    }
    if ($this->security->isGranted('ROLE_OPERATORE')) {
      /** @var OperatoreUser $user */
      if ($user->getId() === $calendar->getOwnerId() || in_array($user->getId(), $calendar->getModeratorsId())) {
        return true;
      }
    }

    return false;
  }

  private function canDelete(Calendar $calendar, User $user)
  {
    if ($this->security->isGranted('ROLE_ADMIN')) {
      return true;
    }
    if ($this->security->isGranted('ROLE_OPERATORE')) {
      /** @var OperatoreUser $user */
      if ($user->getId() === $calendar->getOwnerId()) {
        return true;
      }
    }

    return false;
  }
}