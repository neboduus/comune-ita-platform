<?php


namespace App\Security\Voters;


use App\Entity\Folder;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Security;

class FolderVoter extends Voter
{
  const EDIT = 'edit';
  const VIEW = 'view';


  private $security;

  public function __construct(Security $security)
  {
    $this->security = $security;
  }

  protected function supports($attribute, $subject): bool
  {
    // if the attribute isn't one we support, return false
    if (!in_array($attribute, [self::EDIT, self::VIEW])) {
      return false;
    }

    // only vote on `Folder` objects
    if ($subject && !$subject instanceof Folder) {
      return false;
    }

    return true;
  }

  protected function voteOnAttribute($attribute, $subject, TokenInterface $token): bool
  {
    $user = $token->getUser();

    if (!$user instanceof User) {
      // the user must be logged in; if not, deny access
      return false;
    }

    // you know $subject is a Folder object, thanks to `supports()`
    /** @var Folder $folder */
    $folder = $subject;

    switch ($attribute) {
      case self::EDIT:
        return $this->canEdit($folder, $user);
      case self::VIEW:
        return $this->canView($folder, $user);

    }

    throw new \LogicException('This code should not be reached!');
  }

  private function canView(Folder $folder, User $user): bool
  {
    // if they can edit, they can view
    if ($this->canEdit($folder, $user)) {
      return true;
    }

    return $user->getId() == $folder->getOwnerId();
  }

  private function canEdit(Folder $folder, User $user): bool
  {
    if ($this->security->isGranted('ROLE_ADMIN') || $this->security->isGranted('ROLE_OPERATORE')) {
      return true;
    }
    return false;
  }
}
