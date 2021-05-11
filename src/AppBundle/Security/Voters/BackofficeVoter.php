<?php


namespace AppBundle\Security\Voters;


use AppBundle\BackOffice\BackOfficeInterface;
use AppBundle\Entity\User;
use AppBundle\Services\InstanceService;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Security;

class BackofficeVoter extends Voter
{
  const VIEW = 'view';


  private $security;
  /**
   * @var InstanceService
   */
  private $is;

  public function __construct(Security $security, InstanceService $instanceService)
  {
    $this->security = $security;
    $this->is = $instanceService;
  }

  protected function supports($attribute, $subject)
  {
    // if the attribute isn't one we support, return false
    if (!in_array($attribute, [self::VIEW])) {
      return false;
    }

    // only vote on `string` objects
    if ($subject && !is_string($subject)) {
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
    $backOfficePath = $subject;

    switch ($attribute) {
      case self::VIEW:
        return $this->canView($backOfficePath, $user);
    }

    throw new \LogicException('This code should not be reached!');
  }

  private function canView(string $backOfficePath, User $user)
  {
    if (in_array($backOfficePath, $this->is->getCurrentInstance()->getBackofficeEnabledIntegrations())) {
      return true;
    }
    return false;
  }
}