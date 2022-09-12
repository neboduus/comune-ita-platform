<?php


namespace App\EventListener;


use App\Entity\AdminUser;
use App\Entity\CPSUser;
use App\Entity\OperatoreUser;
use App\Services\InstanceService;
//use App\Services\Metrics\UserMetrics;
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
   * @var InstanceService
   */
  private $instanceService;

  /**
   * LastLoginListener constructor.
   *
   * @param RouterInterface $router
   * @param InstanceService $instanceService
   */
  public function __construct(RouterInterface $router, InstanceService $instanceService)
  {
    $this->router = $router;
    $this->instanceService = $instanceService;
    // TODO: reimplementare le metriche nella classe appena aggiornato il bundle di Prometheus
    // $this->userMetrics = $userMetrics;
  }

  /**
   * @return array
   */
  public static function getSubscribedEvents()
  {
    return array(
      //FOSUserEvents::CHANGE_PASSWORD_COMPLETED => 'onChangePasswordCompleted',
      //FOSUserEvents::RESETTING_RESET_COMPLETED => 'onChangePasswordCompleted',
      AuthenticationEvents::AUTHENTICATION_FAILURE => 'onAuthenticationSuccessFailure',
    );
  }

  /**
   * @param FilterUserResponseEvent $event
   */
  /*public function onChangePasswordCompleted(FilterUserResponseEvent $event)
  {
    $user = $event->getUser();
    if ($user instanceof OperatoreUser || $user instanceof AdminUser) {
      $user->setLastChangePassword(new \DateTime());
      $this->userManager->updateUser($user);
    }
  }*/


  /**
   * @param AuthenticationFailureEvent $event
   */
  public function onAuthenticationSuccessFailure(AuthenticationFailureEvent $event)
  {
    //$this->userMetrics->incLoginFailure($this->instanceService->getCurrentInstance()->getSlug(), 'backend', $event->getAuthenticationException()->getMessage());
  }

}
