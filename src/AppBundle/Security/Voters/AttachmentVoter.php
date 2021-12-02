<?php


namespace AppBundle\Security\Voters;


use AppBundle\Entity\Allegato;
use AppBundle\Entity\CPSUser;
use AppBundle\Entity\OperatoreUser;
use AppBundle\Entity\Pratica;
use AppBundle\Entity\Servizio;
use AppBundle\Entity\User;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Security;

class AttachmentVoter extends Voter
{

  const DOWNLOAD = 'download';
  const EDIT = 'edit';
  const UPLOAD = 'upload';
  const DELETE = 'delete';


  /** @var Security  */
  private $security;

  /** @var Session */
  private $session;

  private $hashValidity;

  /**
   * AttachmentVoter constructor.
   * @param Security $security
   * @param Session $session
   * @param $hashValidity
   */
  public function __construct(Security $security, Session $session, $hashValidity)
  {
    $this->security = $security;
    $this->session = $session;
    $this->hashValidity = $hashValidity;
  }

  /**
   * @param string $attribute
   * @param mixed $subject
   * @return bool
   */
  protected function supports($attribute, $subject)
  {
    if (!in_array($attribute, [
      self::DOWNLOAD,
      self::EDIT,
      self::UPLOAD,
      self::DELETE
    ])) {
      return false;
    }

    if ($subject && !$subject instanceof Allegato) {
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
    /** @var Allegato $attachment */
    $attachment = $subject;

    switch ($attribute) {
      case self::UPLOAD:
      case self::EDIT:
        return $this->canEdit($attachment, $user);
      case self::DOWNLOAD:
        return $this->canDownload($attachment, $user);
      case self::DELETE:
        return $this->canDelete($attachment, $user);

    }

    throw new \LogicException('This code should not be reached!');
  }

  /**
   * @param Allegato $attachment
   * @param User $user
   * @return bool
   */
  private function canDownload(Allegato $attachment, User $user)
  {
    if ($this->security->isGranted('ROLE_ADMIN')) {
      return true;
    }

    if ($this->security->isGranted('ROLE_OPERATORE')) {
      foreach ($attachment->getPratiche() as $pratica) {
        $isOperatoreEnabled = in_array($pratica->getServizio()->getId(), $user->getServiziAbilitati()->toArray());
        if ($pratica->getOperatore() === $user || $isOperatoreEnabled) {
          return true;
        }
      }
    }

    if ($attachment->getOwner() === $user) {
      return true;
    }

    $pratica = $attachment->getPratiche()->first();
    if ($pratica instanceof Pratica) {
      if ($user instanceof CPSUser) {
        $relatedCFs = $pratica->getRelatedCFs();
        $canDownload = (is_array($relatedCFs) && in_array($user->getCodiceFiscale(), $relatedCFs ) || $pratica->getUser() == $user);
      } elseif ($this->session->isStarted() && $this->session->has(Pratica::HASH_SESSION_KEY)) {
        $canDownload = $pratica->isValidHash($this->session->get(Pratica::HASH_SESSION_KEY), $this->hashValidity);
      }
    }

    return $canDownload;

  }

  /**
   * @param Allegato $attachment
   * @param User $user
   * @return bool
   */
  private function canEdit(Allegato $attachment, User $user)
  {
    if ($this->security->isGranted('ROLE_ADMIN')) {
      return true;
    }

    if ($this->security->isGranted('ROLE_OPERATORE')) {
      return true;
    }

    if ($attachment->getOwner() === $user) {
      return true;
    }

    if ($this->session->isStarted() && $attachment->getHash() === hash('sha256', $this->session->getId())) {
      return true;
    }

    return false;
  }

  /**
   * @param Allegato $attachment
   * @param User $user
   * @return bool
   */
  private function canDelete(Allegato $attachment, User $user)
  {

    if ($attachment->getOwner() === $user) {
      return true;
    }

    if ($this->session->isStarted() && $attachment->getHash() === hash('sha256', $this->session->getId())) {
      return true;
    }

    return false;
  }
}
