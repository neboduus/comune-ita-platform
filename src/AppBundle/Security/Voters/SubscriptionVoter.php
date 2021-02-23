<?php


namespace AppBundle\Security\Voters;


use AppBundle\Entity\CPSUser;
use AppBundle\Entity\Subscription;
use AppBundle\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Security;

class SubscriptionVoter extends Voter
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
    if ($subject && !$subject instanceof Subscription) {
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
    /** @var Subscription $subscription */
    $subscription = $subject;

    switch ($attribute) {
      case self::EDIT:
        return $this->canEdit($subscription, $user);
      case self::VIEW:
        return $this->canView($subscription, $user);

    }

    throw new \LogicException('This code should not be reached!');
  }

  private function canView(Subscription $subscription, User $user)
  {
    // if they can edit, they can view
    if ($this->canEdit($subscription, $user)) {
      return true;
    }
    /** @var CPSUser $user */
    return in_array($user->getCodiceFiscale(), array_merge([$subscription->getSubscriber()->getFiscalCode()], $subscription->getRelatedCFs()));
  }

  private function canEdit(Subscription $subscription, User $user)
  {
    if ($this->security->isGranted('ROLE_ADMIN') || $this->security->isGranted('ROLE_OPERATORE')) {
      return true;
    }

    return false;
  }
}