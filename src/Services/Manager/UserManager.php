<?php


namespace App\Services\Manager;


use App\Entity\AdminUser;
use App\Entity\OperatoreUser;
use App\Entity\Servizio;
use App\Entity\User;
use App\Event\KafkaEvent;
use App\Event\SecurityEvent;
use App\Model\Security\SecurityLogInterface;
use App\Services\InstanceService;
use App\Services\MailerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Csrf\TokenGenerator\TokenGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;


class UserManager
{
  private EntityManagerInterface $entityManager;
  private TranslatorInterface $translator;
  private InstanceService $instanceService;
  private RouterInterface $router;
  private MailerService $mailerService;
  private TokenGeneratorInterface $tokenGenerator;
  private UserPasswordEncoderInterface $encoder;
  private string $defaultSender;
  private EventDispatcherInterface $dispatcher;


  /**
   * UserManager constructor.
   * @param EntityManagerInterface $entityManager
   * @param TranslatorInterface $translator
   * @param InstanceService $instanceService
   * @param RouterInterface $router
   * @param MailerService $mailerService
   * @param TokenGeneratorInterface $tokenGenerator
   * @param UserPasswordEncoderInterface $encoder
   * @param EventDispatcherInterface $dispatcher
   * @param string $defaultSender
   */
  public function __construct(
    EntityManagerInterface $entityManager,
    TranslatorInterface $translator,
    InstanceService $instanceService,
    RouterInterface $router,
    MailerService $mailerService,
    TokenGeneratorInterface $tokenGenerator,
    UserPasswordEncoderInterface $encoder,
    EventDispatcherInterface $dispatcher,
    string $defaultSender
  )
  {
    $this->entityManager = $entityManager;
    $this->translator = $translator;
    $this->instanceService = $instanceService;
    $this->router = $router;
    $this->mailerService = $mailerService;
    $this->tokenGenerator = $tokenGenerator;
    $this->encoder = $encoder;
    $this->defaultSender = $defaultSender;
    $this->dispatcher = $dispatcher;
  }

  public function save(UserInterface $user)
  {
    $this->entityManager->persist($user);
    $this->entityManager->flush();

    if ($user instanceof AdminUser) {
      $this->dispatcher->dispatch(new SecurityEvent(SecurityLogInterface::ACTION_USER_ADMIN_CREATED, $user));
    } elseif ($user instanceof OperatoreUser) {
      $this->dispatcher->dispatch(new SecurityEvent(SecurityLogInterface::ACTION_USER_OPERATOR_CREATED, $user));
    }
  }

  public function remove(UserInterface $user)
  {
    $this->entityManager->remove($user);
    $this->entityManager->flush();

    if ($user instanceof AdminUser) {
      $this->dispatcher->dispatch(new SecurityEvent(SecurityLogInterface::ACTION_USER_ADMIN_REMOVED, $user));
    } elseif ($user instanceof OperatoreUser) {
      $this->dispatcher->dispatch(new SecurityEvent(SecurityLogInterface::ACTION_USER_OPERATOR_REMOVED, $user));
    }
  }

  /**
   * @param User $user
   */
  public function resetPassword(User $user)
  {
    $user->setConfirmationToken($this->tokenGenerator->generateToken());
    $user->setPasswordRequestedAt(new \DateTime());
    $this->entityManager->flush();

    $this->mailerService->dispatchMail(
      $this->defaultSender,
      $this->instanceService->getCurrentInstance()->getName(),
      $user->getEmail(),
      $user->getFullName(),
      $this->translator->trans('user.reset_password.message'),
      $this->translator->trans('user.reset_password.subject'),
      $this->instanceService->getCurrentInstance(),
      [
        [
          'link' => $this->router->generate(
            'reset_password_confirm',
            ['token' => $user->getConfirmationToken()],
            UrlGeneratorInterface::ABSOLUTE_URL
          ),
          'label' => $this->translator->trans('user.reset_password.btn'),
        ],
      ]
    );

    $this->dispatcher->dispatch(new SecurityEvent(SecurityLogInterface::ACTION_USER_RESET_PASSWORD_REQUEST, ['email' => $user->getEmail()]));
  }


  public function changePassword(User $user, $plainPassword) {
    $encodedPassword = $this->encoder->encodePassword($user, $plainPassword);
    $user->setPassword($encodedPassword);
    $user->setConfirmationToken(null);
    $user->setLastChangePassword();
    $this->entityManager->persist($user);
    $this->entityManager->flush();

    $this->dispatcher->dispatch(new SecurityEvent(SecurityLogInterface::ACTION_USER_RESET_PASSWORD_SUCCESS, ['email' => $user->getEmail()]));
  }
}
