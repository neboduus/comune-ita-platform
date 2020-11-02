<?php

namespace AppBundle\Services;

use AppBundle\Dto\UserAuthenticationData;
use AppBundle\Entity\CPSUser;
use AppBundle\Entity\User;
use AppBundle\Entity\UserSession;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserSessionService
{
  private $session;

  private $entityManager;

  private $repository;

  private $request;

  private $currentUserSessionData;

  public function __construct(
    SessionInterface $session,
    EntityManagerInterface $entityManager
  ) {
    $this->session = $session;
    $this->entityManager = $entityManager;
    $this->repository = $this->entityManager->getRepository(UserSession::class);
    $this->request = Request::createFromGlobals();
  }

  public function getCurrentUserAuthenticationData(User $currentUser = null)
  {
    return $this->getCurrentUserSessionData($currentUser)->getAuthenticationData();
  }

  /**
   * @param User|null $currentUser
   * @return UserSession
   */
  public function getCurrentUserSessionData(User $currentUser = null)
  {
    if ($this->currentUserSessionData === null) {
      if ($this->session->has('sdc_user_session_data')){
        $this->currentUserSessionData = $this->repository->find($this->session->get('sdc_user_session_data'));
      }
      if (!$this->currentUserSessionData instanceof UserSession) {
        $this->createCurrentUserSessionData($currentUser);
      }
    }

    return $this->currentUserSessionData;
  }

  public function createCurrentUserSessionData(
    UserInterface $currentUser,
    array $sessionData = [],
    UserAuthenticationData $authenticationData = null
  ) {
    $userSession = new UserSession();
    $userSession->setUserId($currentUser->getId());
    $userSession->setEnvironment($this->request->headers->get('User-Agent'));
    $userSession->setClientIp($this->request->getClientIp());
    $userSession->setSuspiciousActivity(false);
    $userSession->setSessionData($sessionData);
    if (!$authenticationData) {
      $authenticationData = UserAuthenticationData::fromArray(['authenticationMethod' => CPSUser::IDP_NONE,]);
    }
    $userSession->setAuthenticationData($authenticationData);

    $this->entityManager->persist($userSession);
    $this->entityManager->flush();

    $this->currentUserSessionData = $userSession;
    $this->session->set('sdc_user_session_data', $userSession->getId());

    return true;
  }

}
