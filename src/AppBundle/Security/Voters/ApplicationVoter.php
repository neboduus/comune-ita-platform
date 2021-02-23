<?php


namespace AppBundle\Security\Voters;


use AppBundle\Entity\OperatoreUser;
use AppBundle\Entity\Pratica;
use AppBundle\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Security;

class ApplicationVoter extends Voter
{

  const EDIT = 'edit';
  const VIEW = 'view';
  const SUBMIT = 'submit';
  const ASSIGN = 'assign';
  const ACCEPT_OR_REJECT = 'accept_or_reject';
  const WITHDRAW = 'withdraw';


  private $security;

  public function __construct(Security $security)
  {
    $this->security = $security;
  }

  protected function supports($attribute, $subject)
  {
    // if the attribute isn't one we support, return false
    if (!in_array($attribute, [
      self::EDIT,
      self::VIEW,
      self::SUBMIT,
      self::ASSIGN,
      self::ACCEPT_OR_REJECT,
      self::WITHDRAW
    ])) {
      return false;
    }

    // only vote on `Pratica` objects
    if ($subject && !$subject instanceof Pratica) {
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

    // you know $subject is a Pratica object, thanks to `supports()`
    /** @var Pratica $pratica */
    $pratica = $subject;

    switch ($attribute) {
      case self::EDIT:
        return $this->canEdit($pratica, $user);
      case self::VIEW:
        return $this->canView($pratica, $user);
      case self::SUBMIT:
        return $this->canSubmit($pratica, $user);
      case self::ASSIGN:
        return $this->canAssign($pratica, $user);
      case self::ACCEPT_OR_REJECT:
        return $this->canAcceptOrReject($pratica, $user);
      case self::WITHDRAW:
        return $this->canWithdraw($pratica, $user);


    }

    throw new \LogicException('This code should not be reached!');
  }

  private function canView(Pratica $pratica, User $user)
  {
    // if they can edit, they can view
    if ($this->canEdit($pratica, $user)) {
      return true;
    }
    return $user === $pratica->getUser();
  }

  private function canEdit(Pratica $pratica, User $user)
  {
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

  private function canSubmit(Pratica $pratica, User $user)
  {
    // if they can edit, they can submit
    if ($this->canEdit($pratica, $user)) {
      return true;
    }
    return $user === $pratica->getUser();
  }

  private function canAssign(Pratica $pratica, User $user)
  {
    if ($this->security->isGranted('ROLE_OPERATORE')) {
      /** @var OperatoreUser $user */
      if (in_array($pratica->getServizio()->getId(), $user->getServiziAbilitati()->toArray())) {
        return true;
      }
    }
    return false;
  }

  private function canAcceptOrReject(Pratica $pratica, User $user)
  {
    if ($this->security->isGranted('ROLE_OPERATORE')) {
      /** @var OperatoreUser $user */
      if ($user === $pratica->getOperatore()) {
        return true;
      }
    }
    return false;
  }

  private function canWithdraw(Pratica $pratica, User $user)
  {
    return $user->getId() === $pratica->getUser()->getId();
  }
}