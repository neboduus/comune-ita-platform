<?php


namespace App\EventListener;


use App\Entity\AdminUser;
use App\Entity\OperatoreUser;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\SecurityEvents;

class UserSecuritySubscriber  implements EventSubscriberInterface
{
    /**
     * @var Router
     */
    private $router;

  /**
   * LastLoginListener constructor.
   *
   * @param Router $router
   */
    public function __construct(Router $router)
    {
        $this->router = $router;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return array(
            //FOSUserEvents::CHANGE_PASSWORD_COMPLETED => 'onChangePasswordCompleted',
            //FOSUserEvents::RESETTING_RESET_COMPLETED => 'onChangePasswordCompleted'
        );
    }

    /*
    public function onChangePasswordCompleted(FilterUserResponseEvent $event)
    {
        $user = $event->getUser();
        if ($user instanceof OperatoreUser || $user instanceof AdminUser) {
            $user->setLastChangePassword(new \DateTime());
            $this->userManager->updateUser($user);
        }
    }*/
}
