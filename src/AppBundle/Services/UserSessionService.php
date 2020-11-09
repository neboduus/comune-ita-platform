<?php

namespace AppBundle\Services;

use AppBundle\Dto\UserAuthenticationData;
use AppBundle\Entity\CPSUser;
use AppBundle\Entity\User;
use AppBundle\Entity\UserSession;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
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

  private $logger;

  public function __construct(
    SessionInterface $session,
    EntityManagerInterface $entityManager,
    LoggerInterface $logger
  ) {
    $this->session = $session;
    $this->entityManager = $entityManager;
    $this->repository = $this->entityManager->getRepository(UserSession::class);
    $this->request = Request::createFromGlobals();
    $this->logger = $logger;
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
    UserAuthenticationData $authenticationData = null,
    $store = false
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
    $this->logger->info('Create UserSession data', ['user' => $currentUser, 'data' => $userSession]);
    $this->entityManager->persist($userSession);

    if ($store) {
      $this->entityManager->flush();
    }

    $this->currentUserSessionData = $userSession;
    $this->session->set('sdc_user_session_data', $userSession->getId());

    return true;
  }

  public function storeCurrentUserSessionData(
    UserInterface $currentUser,
    array $sessionData = [],
    UserAuthenticationData $authenticationData = null
  ) {
    return $this->createCurrentUserSessionData($currentUser, $sessionData, $authenticationData, true);
  }

}
