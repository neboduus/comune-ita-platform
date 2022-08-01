<?php


namespace App\Security\Voters;


use App\Entity\Allegato;
use App\Entity\CPSUser;
use App\Entity\OperatoreUser;
use App\Entity\Pratica;
use App\Entity\RichiestaIntegrazione;
use App\Entity\RispostaOperatore;
use App\Entity\Servizio;
use App\Entity\User;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
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

  /** @var SessionInterface */
  private $session;

  private $hashValidity;

  /**
   * AttachmentVoter constructor.
   * @param Security $security
   * @param SessionInterface $session
   * @param $hashValidity
   */
  public function __construct(Security $security, SessionInterface $session, $hashValidity)
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

      if ($attachment instanceof RichiestaIntegrazione) {
        $pratica = $attachment->getPratica();
        $isOperatoreEnabled = in_array($pratica->getServizio()->getId(), $user->getServiziAbilitati()->toArray());
        if ($pratica->getOperatore() === $user || $isOperatoreEnabled) {
          return true;
        }
      }

      // Fixme: permetto sempre il download della risposta da parte degli operatori. Da sistemare una volta riorganizzati gli allegati
      if ($attachment instanceof RispostaOperatore) {
        return true;
      }

    }

    if ($attachment->getOwner() === $user) {
      return true;
    }

    $canDownload = false;
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
