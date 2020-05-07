<?php

namespace App\EventListener;

use App\Entity\AdminUser;
use App\Entity\OperatoreUser;
use App\Entity\User;
use App\Event\ChangePasswordSuccessEvent;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class UserSecuritySubscriber implements EventSubscriberInterface
{
    /**
     * @var UserRepository
     */
    protected $userManager;
    /**
     * @var Router
     */
    private $router;

    public function __construct(EntityManagerInterface $em, RouterInterface $router)
    {
        $this->userManager = $em->getRepository(User::class);
        $this->router = $router;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return array(
            ChangePasswordSuccessEvent::class => 'onChangePasswordCompleted'
        );
    }

    /**
     * @param ChangePasswordSuccessEvent $event
     * @throws \Exception
     */
    public function onChangePasswordCompleted(ChangePasswordSuccessEvent $event)
    {
        $user = $event->getUser();
        if ($user instanceof OperatoreUser || $user instanceof AdminUser) {
            $user->setLastChangePassword(new \DateTime());
            $this->userManager->updateUser($user);
        }
    }
}
