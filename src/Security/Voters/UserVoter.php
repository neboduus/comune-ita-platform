<?php


namespace App\Security\Voters;


use App\Entity\AdminUser;
use App\Entity\CPSUser;
use App\Entity\OperatoreUser;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Security;

class UserVoter extends Voter
{
  const EDIT = 'edit';
  const VIEW = 'view';
  const DELETE = 'delete';


  private Security $security;

  public function __construct(Security $security)
  {
    $this->security = $security;
  }

  protected function supports($attribute, $subject): bool
  {
    // if the attribute isn't one we support, return false
    if (!in_array($attribute, [self::EDIT, self::VIEW, self::DELETE])) {
      return false;
    }

    // only vote on `User` objects
    if ($subject && !$subject instanceof User) {
      return false;
    }

    return true;
  }

  protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
  {
    $loggedUser = $token->getUser();

    if (!$loggedUser instanceof User) {
      // the user must be logged in; if not, deny access
      return false;
    }

    // you know $subject is a CpsUser object, thanks to `supports()`
    /** @var User $user */
    $user = $subject;

    switch ($attribute) {
      case self::EDIT:
        return $this->canEdit($user, $loggedUser);
      case self::VIEW:
        return $this->canView($user, $loggedUser);
      case self::DELETE:
        return $this->canDelete($user, $loggedUser);

    }

    throw new \LogicException('This code should not be reached!');
  }

  private function canView(User $user, User $loggedUser)
  {
    // if they can edit, they can view
    if ($this->canEdit($user, $loggedUser)) {
      return true;
    }

    // An admin or an operator can always see admins and operators
    if ($this->security->isGranted('ROLE_ADMIN') || $this->security->isGranted('ROLE_OPERATORE')) {
      return true;
    }

    return false;
  }

  private function canEdit(User $user, User $loggedUser): bool
  {
    // An admin can always edit operators or users
    if ($this->security->isGranted('ROLE_ADMIN') && (!$user instanceof AdminUser)) {
      return true;
    }

    // An operator can always edit users
    if ($this->security->isGranted('ROLE_OPERATORE') && ($user instanceof CPSUser)) {
      return true;
    }

    // A user can always edit himself
    return $loggedUser === $user;
  }

  private function canDelete(User $user, User $loggedUser): bool
  {
    // An admin can always delete operators or himself
    if ($this->security->isGranted('ROLE_ADMIN') && ($user instanceof OperatoreUser || $loggedUser === $user)) {
      return true;
    }

    return false;
  }
}
