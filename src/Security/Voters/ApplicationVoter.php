<?php


namespace App\Security\Voters;


use App\Entity\CPSUser;
use App\Entity\OperatoreUser;
use App\Entity\Pratica;
use App\Entity\Servizio;
use App\Entity\User;
use App\Handlers\Servizio\ForbiddenAccessException;
use App\Handlers\Servizio\ServizioHandlerRegistry;
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
  const COMPILE = 'compile';
  const DELETE = 'delete';


  /**
   * @var ServizioHandlerRegistry
   */
  private $servizioHandlerRegistry;
  /**
   * @var Security
   */
  private $security;

  public function __construct(Security $security, ServizioHandlerRegistry $servizioHandlerRegistry)
  {
    $this->security = $security;
    $this->servizioHandlerRegistry = $servizioHandlerRegistry;
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
      self::WITHDRAW,
      self::COMPILE,
      self::DELETE
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
      case self::COMPILE:
        return $this->canCompile($pratica, $user);
      case self::DELETE:
        return $this->canDelete($pratica, $user);
    }

    throw new \LogicException('This code should not be reached!');
  }

  private function canView(Pratica $pratica, User $user)
  {
    // if they can edit, they can view
    if ($this->canEdit($pratica, $user)) {
      return true;
    }

    $isTheOwner = $pratica->getUser() === $user;
    $cfs = $pratica->getRelatedCFs();
    if (!is_array($cfs)) {
      $cfs = [$cfs];
    }
    $isRelated = $user instanceof CPSUser && in_array($user->getCodiceFiscale(), $cfs);

    return $isTheOwner || $isRelated;
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
    // Non è possibile ritirare una pratica se non è abilitato il ritiro, se è presente un dovuto di pagamento o se l'utente non è il richiedente
    if (!$pratica->getServizio()->isAllowWithdraw() || !empty($pratica->getPaymentData()) || $pratica->getUser()->getId() !== $user->getId()) {
      return false;
    }

    // Se il servizio ha un workflow di tipo inoltro e la pratica è stata "inoltrata" NON deve comparire il pulsante ritira.
    if ($pratica->getServizio()->getWorkflow() == Servizio::WORKFLOW_FORWARD && $pratica->getStatus() !== Pratica::STATUS_PRE_SUBMIT) {
      return false;
    }

    // Se il servizio ha la protocollazione attiva ed e la pratica è in STATUS_PRE_SUBMIT, altrimenti genera un errore in fase di protocollazzione del documento di ritiro
    if ($pratica->getServizio()->isProtocolRequired() && $pratica->getStatus() === Pratica::STATUS_PRE_SUBMIT) {
      return false;
    }

    return in_array($pratica->getStatus(), [Pratica::STATUS_PRE_SUBMIT, Pratica::STATUS_SUBMITTED, Pratica::STATUS_REGISTERED, Pratica::STATUS_PENDING]);
  }

  private function canCompile(Pratica $pratica, User $user)
  {
    $canCompile = false;
    // se la pratica è in bozza oppure in attesa di creazione del pagamento
    if (in_array($pratica->getStatus(), [Pratica::STATUS_DRAFT, Pratica::STATUS_DRAFT_FOR_INTEGRATION]) || $pratica->needsPaymentCreation() && $pratica->getUser()->getId() == $user->getId()) {
      $handler = $this->servizioHandlerRegistry->getByName($pratica->getServizio()->getHandler());
      try {
        $handler->canAccess($pratica->getServizio(), $pratica->getEnte());
        $canCompile = true;
      } catch (ForbiddenAccessException $e) {
        $canCompile = false;
      }
    }
    return $canCompile;
  }

  private function canDelete(Pratica $pratica, User $user)
  {
    return $user === $pratica->getUser();
  }
}
