<?php


namespace App\Security\Voters;


use App\Entity\CPSUser;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Security;

class UserVoter extends Voter
{
  const EDIT = 'edit';
  const VIEW = 'view';


  private $security;

  public function __construct(Security $security)
  {
    $this->security = $security;
  }

  protected function supports($attribute, $subject)
  {
    // if the attribute isn't one we support, return false
    if (!in_array($attribute, [self::EDIT, self::VIEW])) {
      return false;
    }

    // only vote on `CpsUser` objects
    if ($subject && !$subject instanceof CPSUser) {
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

    // you know $subject is a CpsUser object, thanks to `supports()`
    /** @var CPSUser $cpsUser */
    $cpsUser = $subject;

    switch ($attribute) {
      case self::EDIT:
        return $this->canEdit($cpsUser, $user);
      case self::VIEW:
        return $this->canView($cpsUser, $user);

    }

    throw new \LogicException('This code should not be reached!');
  }

  private function canView(CPSUser $cpsUser, User $user)
  {
    // if they can edit, they can view
    if ($this->canEdit($cpsUser, $user)) {
      return true;
    }

    return $user === $cpsUser;
  }

  private function canEdit(CPSUser $cpsUser, User $user)
  {
    if ($this->security->isGranted('ROLE_ADMIN') || $this->security->isGranted('ROLE_OPERATORE')) {
      return true;
    }
    return $user === $cpsUser;
  }
}
