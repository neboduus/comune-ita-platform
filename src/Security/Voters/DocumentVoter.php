<?php


namespace App\Security\Voters;


use App\Entity\CPSUser;
use App\Entity\Document;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Security;

class DocumentVoter extends Voter
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

    // only vote on `Document` objects
    if ($subject && !$subject instanceof Document) {
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

    // you know $subject is a Document object, thanks to `supports()`
    /** @var Document $document */
    $document = $subject;

    switch ($attribute) {
      case self::EDIT:
        return $this->canEdit($document, $user);
      case self::VIEW:
        return $this->canView($document, $user);

    }

    throw new \LogicException('This code should not be reached!');
  }

  private function canView(Document $document, User $user)
  {
    // if they can edit, they can view
    if ($this->canEdit($document, $user)) {
      return true;
    }

    /** @var CPSUser $user */
    return in_array($user->getCodiceFiscale(), array_merge([$document->getOwner()->getCodiceFiscale()], $document->getRelatedCFs()));

  }

  private function canEdit(Document $document, User $user)
  {
    if ($this->security->isGranted('ROLE_ADMIN') || $this->security->isGranted('ROLE_OPERATORE')) {
      return true;
    }
    return false;
  }
}
