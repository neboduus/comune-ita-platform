<?php


namespace AppBundle\EventListener;


use AppBundle\Entity\AdminUser;
use AppBundle\Entity\CPSUser;
use AppBundle\Entity\OperatoreUser;
use AppBundle\Services\InstanceService;
use AppBundle\Services\Metrics\UserMetrics;
use FOS\UserBundle\Event\UserEvent;
use FOS\UserBundle\Event\FilterUserResponseEvent;
use FOS\UserBundle\FOSUserEvents;
use FOS\UserBundle\Model\UserManagerInterface;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\AuthenticationEvents;
use Symfony\Component\Security\Core\Event\AuthenticationEvent;
use Symfony\Component\Security\Core\Event\AuthenticationFailureEvent;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\SecurityEvents;

class UserSecuritySubscriber implements EventSubscriberInterface
{
  /**
   * @var Router
   */
  private $router;

  /**
   * @var UserManagerInterface
   */
  protected $userManager;
  /**
   * @var InstanceService
   */
  private $instanceService;
  /**
   * @var UserMetrics
   */
  private $userMetrics;

  /**
   * LastLoginListener constructor.
   *
   * @param UserManagerInterface $userManager
   * @param RouterInterface $router
   * @param InstanceService $instanceService
   * @param UserMetrics $userMetrics
   */
  public function __construct(UserManagerInterface $userManager, RouterInterface $router, InstanceService $instanceService, UserMetrics $userMetrics)
  {
    $this->userManager = $userManager;
    $this->router = $router;
    $this->instanceService = $instanceService;
    $this->userMetrics = $userMetrics;
  }

  /**
   * @return array
   */
  public static function getSubscribedEvents()
  {
    return array(
      FOSUserEvents::CHANGE_PASSWORD_COMPLETED => 'onChangePasswordCompleted',
      FOSUserEvents::RESETTING_RESET_COMPLETED => 'onChangePasswordCompleted',
      AuthenticationEvents::AUTHENTICATION_FAILURE => 'onAuthenticationSuccessFailure',
    );
  }

  /**
   * @param FilterUserResponseEvent $event
   */
  public function onChangePasswordCompleted(FilterUserResponseEvent $event)
  {
    $user = $event->getUser();
    if ($user instanceof OperatoreUser || $user instanceof AdminUser) {
      $user->setLastChangePassword(new \DateTime());
      $this->userManager->updateUser($user);
    }
  }


  /**
   * @param AuthenticationFailureEvent $event
   */
  public function onAuthenticationSuccessFailure(AuthenticationFailureEvent $event)
  {
    $this->userMetrics->incLoginFailure($this->instanceService->getCurrentInstance()->getSlug(), 'backend', $event->getAuthenticationException()->getMessage());
  }

}
