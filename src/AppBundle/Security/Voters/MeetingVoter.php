<?php


namespace AppBundle\Security\Voters;


use AppBundle\Entity\Meeting;
use AppBundle\Entity\OperatoreUser;
use AppBundle\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Security;

class MeetingVoter extends Voter
{
  const EDIT = 'edit';
  const VIEW = 'view';
  const DELETE = 'delete';


  private $security;

  public function __construct(Security $security)
  {
    $this->security = $security;
  }

  protected function supports($attribute, $subject)
  {
    // if the attribute isn't one we support, return false
    if (!in_array($attribute, [self::EDIT, self::VIEW, self::DELETE])) {
      return false;
    }

    // only vote on `Meeting` objects
    if ($subject && !$subject instanceof Meeting) {
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

    // you know $subject is a Meeting object, thanks to `supports()`
    /** @var Meeting $meeting */
    $meeting = $subject;

    switch ($attribute) {
      case self::EDIT:
        return $this->canEdit($meeting, $user);
      case self::VIEW:
        return $this->canView($meeting, $user);
      case self::DELETE:
        return $this->canDelete($meeting, $user);
    }

    throw new \LogicException('This code should not be reached!');
  }

  private function canView(Meeting $meeting, User $user)
  {
    // if they can edit, they can view
    if ($this->canEdit($meeting, $user)) {
      return true;
    }
    return $user->getId() === $meeting->getUserId();
  }

  private function canEdit(Meeting $meeting, User $user)
  {
    $calendar = $meeting->getCalendar();
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

  private function canDelete(Meeting $meeting, User $user)
  {
    // if they can edit, they can delete
    if ($this->canEdit($meeting, $user)) {
      return true;
    }
    return false;
  }
}