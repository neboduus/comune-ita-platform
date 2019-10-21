<?php


namespace AppBundle\EventListener;


use AppBundle\Entity\AdminUser;
use AppBundle\Entity\OperatoreUser;
use FOS\UserBundle\Event\UserEvent;
use FOS\UserBundle\Event\FilterUserResponseEvent;
use FOS\UserBundle\FOSUserEvents;
use FOS\UserBundle\Model\UserManagerInterface;
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
     * @var UserManagerInterface
     */
    protected $userManager;

    /**
     * LastLoginListener constructor.
     *
     * @param UserManagerInterface $userManager
     */
    public function __construct(UserManagerInterface $userManager, Router $router)
    {
        $this->userManager = $userManager;
        $this->router = $router;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return array(
            FOSUserEvents::CHANGE_PASSWORD_COMPLETED => 'onChangePasswordCompleted',
            FOSUserEvents::RESETTING_RESET_COMPLETED => 'onChangePasswordCompleted'
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

}