<?php


namespace App\Services\Manager;


use App\Entity\User;
use App\Services\InstanceService;
use App\Services\MailerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Csrf\TokenGenerator\TokenGeneratorInterface;
use Symfony\Component\Translation\TranslatorInterface;


class UserManager
{
  /** @var EntityManagerInterface */
  private $entityManager;

  /** @var TranslatorInterface */
  private $translator;

  /** @var InstanceService */
  private $instanceService;

  /** @var RouterInterface */
  private $router;

  /** @var MailerService */
  private $mailerService;

  /**
   * @var TokenGeneratorInterface
   */
  private $tokenGenerator;

  /**
   * @var UserPasswordEncoderInterface $encoder
   */
  private $encoder;

  private $defaultSender;


  /**
   * UserManager constructor.
   * @param EntityManagerInterface $entityManager
   * @param TranslatorInterface $translator
   * @param InstanceService $instanceService
   * @param TokenGeneratorInterface $tokenGenerator
   * @param RouterInterface $router
   * @param MailerService $mailerService
   * @param UserPasswordEncoderInterface $encoder
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
  }


  public function changePassword(User $user, $plainPassword) {
    $encodedPassword = $this->encoder->encodePassword($user, $plainPassword);
    $user->setPassword($encodedPassword);
    $user->setConfirmationToken(null);
    $user->setLastChangePassword();
    $this->entityManager->persist($user);
    $this->entityManager->flush();
  }
}
