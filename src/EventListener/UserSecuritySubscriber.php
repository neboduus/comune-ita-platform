<?php


namespace App\EventListener;

use App\Event\SecurityEvent;
use App\Logging\SecurityLogFactory;
use App\Model\Security\SecurityLogInterface;
use App\Services\KafkaService;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\AuthenticationEvents;
use Symfony\Component\Security\Core\Event\AuthenticationFailureEvent;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\SecurityEvents;
use Throwable;

class UserSecuritySubscriber implements EventSubscriberInterface
{

  private SecurityLogFactory $factory;
  private RequestStack $requestStack;
  private KafkaService $kafkaService;
  private LoggerInterface $logger;
  private TokenStorageInterface $tokenStorage;

  public function __construct(SecurityLogFactory $factory, RequestStack $requestStack, TokenStorageInterface $tokenStorage, KafkaService $kafkaService, LoggerInterface $logger)
  {
    $this->factory = $factory;
    $this->requestStack = $requestStack;
    $this->kafkaService = $kafkaService;
    $this->logger = $logger;
    $this->tokenStorage = $tokenStorage;
  }

  public static function getSubscribedEvents()
  {
    return array(
      //FOSUserEvents::CHANGE_PASSWORD_COMPLETED => 'onChangePasswordCompleted',
      //FOSUserEvents::RESETTING_RESET_COMPLETED => 'onChangePasswordCompleted',
      SecurityEvent::class => 'onSecurityEvent',
      SecurityEvents::INTERACTIVE_LOGIN => 'onInteractiveLogin',
      AuthenticationEvents::AUTHENTICATION_FAILURE => 'onAuthenticationFailure',
    );
  }

  /*public function onChangePasswordCompleted(FilterUserResponseEvent $event)
  {
    $user = $event->getUser();
    if ($user instanceof OperatoreUser || $user instanceof AdminUser) {
      $user->setLastChangePassword(new \DateTime());
      $this->userManager->updateUser($user);
    }
  }*/

  public function onSecurityEvent(SecurityEvent $event)
  {
    $this->generateSecurityLog($event->getType(), $this->getUser(), $event->getSubject());
  }

  public function onInteractiveLogin(InteractiveLoginEvent $event)
  {
    $user = $event->getAuthenticationToken()->getUser();
    $this->generateSecurityLog(SecurityLogInterface::ACTION_USER_LOGIN_SUCCESS, $user);
  }


  /**
   * @param AuthenticationFailureEvent $event
   */
  public function onAuthenticationFailure(AuthenticationFailureEvent $event)
  {
    $this->generateSecurityLog(SecurityLogInterface::ACTION_USER_LOGIN_FAILED, null);
  }

  private function generateSecurityLog($type, ?UserInterface $user, $subject = null)
  {
    try {
      $request = $this->requestStack->getCurrentRequest();
      $securityLog = $this->factory->getSecurityLog($type, $user, $request, $subject);
      $this->kafkaService->produceMessage($securityLog);
    } catch (Throwable $e) {
      $this->logger->error($e->getMessage());
    }
  }

  private function getUser()
  {
    if (null === $token = $this->tokenStorage->getToken()) {
      return null;
    }

    if (!is_object($user = $token->getUser())) {
      return null;
    }
    return $user;
  }

}
