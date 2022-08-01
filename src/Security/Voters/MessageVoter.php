<?php


namespace App\Security\Voters;


use App\Entity\Message;
use App\Entity\OperatoreUser;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Security;

class MessageVoter extends Voter
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

    // only vote on `Message` objects
    if ($subject && !$subject instanceof Message) {
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

    // you know $subject is a Message object, thanks to `supports()`
    /** @var Message $message */
    $message = $subject;

    switch ($attribute) {
      case self::EDIT:
        return $this->canEdit($message, $user);
      case self::VIEW:
        return $this->canView($message, $user);

    }

    throw new \LogicException('This code should not be reached!');
  }

  private function canView(Message $message, User $user)
  {
    $pratica = $message->getApplication();
    // if they can edit, they can view
    if ($this->canEdit($message, $user)) {
      return true;
    }
    return $user === $pratica->getUser();
  }

  private function canEdit(Message $message, User $user)
  {
    $pratica = $message->getApplication();
    if ($this->security->isGranted('ROLE_ADMIN')) {
      return true;
    }
    if ($this->security->isGranted('ROLE_OPERATORE')) {
      /** @var OperatoreUser $user */
      if (in_array($pratica->getServizio()->getId(), $user->getServiziAbilitati()->toArray())) {
        return true;
      }
    }
    return false;
  }
}
