<?php


namespace AppBundle\Security\Voters;


use AppBundle\Entity\CPSUser;
use AppBundle\Entity\Subscriber;
use AppBundle\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Security;

class SubscriberVoter extends Voter
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

    // only vote on `Subscription` objects
    if ($subject && !$subject instanceof Subscriber) {
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

    // you know $subject is a Subscription object, thanks to `supports()`
    /** @var Subscriber $subscriber */
    $subscriber = $subject;

    switch ($attribute) {
      case self::EDIT:
        return $this->canEdit($subscriber, $user);
      case self::VIEW:
        return $this->canView($subscriber, $user);

    }

    throw new \LogicException('This code should not be reached!');
  }

  private function canView(Subscriber $subscriber, User $user)
  {
    // if they can edit, they can view
    if ($this->canEdit($subscriber, $user)) {
      return true;
    }
    /** @var CPSUser $user */
    foreach ($subscriber->getSubscriptions() as $subscription) {
      if (in_array($user->getCodiceFiscale(), $subscription->getRelatedCFs())) {
        return true;
      }
    }
    return false;
  }

  private function canEdit(Subscriber $subscriber, User $user)
  {
    if ($this->security->isGranted('ROLE_ADMIN') || $this->security->isGranted('ROLE_OPERATORE')) {
      return true;
    }

    /** @var CPSUser $user */
    if ($user->getCodiceFiscale() == $subscriber->getFiscalCode()) {
      return true;
    }

    return false;
  }
}
