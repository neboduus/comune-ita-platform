<?php

namespace App\Security;

use App\Entity\OperatoreUser;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\Exception\InvalidCsrfTokenException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Guard\Authenticator\AbstractFormLoginAuthenticator;
use Symfony\Component\Security\Guard\PasswordAuthenticatedInterface;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
use Symfony\Contracts\Translation\TranslatorInterface;

class LoginFormAuthenticator extends AbstractFormLoginAuthenticator implements PasswordAuthenticatedInterface
{
  use TargetPathTrait;

  /** @var EntityManagerInterface  */
  private EntityManagerInterface $entityManager;

  /** @var UrlGeneratorInterface  */
  private UrlGeneratorInterface $urlGenerator;

  /** @var CsrfTokenManagerInterface  */
  private CsrfTokenManagerInterface $csrfTokenManager;

  /** @var UserPasswordEncoderInterface  */
  private UserPasswordEncoderInterface $passwordEncoder;

  /** @var CsrfTokenManagerInterface  */
  private CsrfTokenManagerInterface $refreshTokenManager;

  /** @var AuthorizationCheckerInterface  */
  private AuthorizationCheckerInterface $authorizationChecker;

  /** @var TranslatorInterface  */
  private TranslatorInterface $translator;

  /**
   * LoginFormAuthenticator constructor.
   *
   * @param EntityManagerInterface $entityManager
   * @param UrlGeneratorInterface $urlGenerator
   * @param CsrfTokenManagerInterface $csrfTokenManager
   * @param UserPasswordEncoderInterface $passwordEncoder
   * @param AuthorizationCheckerInterface $authorizationChecker
   * @param TranslatorInterface $translator
   */
  public function __construct(
    EntityManagerInterface $entityManager,
    UrlGeneratorInterface $urlGenerator,
    CsrfTokenManagerInterface $csrfTokenManager,
    UserPasswordEncoderInterface $passwordEncoder,
    AuthorizationCheckerInterface $authorizationChecker,
    TranslatorInterface $translator
  )
  {
    $this->entityManager = $entityManager;
    $this->urlGenerator = $urlGenerator;
    $this->csrfTokenManager = $csrfTokenManager;
    $this->passwordEncoder = $passwordEncoder;
    $this->authorizationChecker = $authorizationChecker;
    $this->translator = $translator;
  }

  /**
   * @param Request $request
   *
   * @return bool
   */
  public function supports(Request $request): bool
  {
    return 'security_login' === $request->attributes->get('_route')
      && $request->isMethod('POST');
  }

  /**
   * @param Request $request
   *
   * @return array|mixed
   */
  public function getCredentials(Request $request)
  {
    $credentials = [
      'username' => $request->request->get('_username'),
      'password' => $request->request->get('_password'),
      'csrf_token' => $request->request->get('_csrf_token'),
    ];

    $request->getSession()->set(
      Security::LAST_USERNAME,
      $credentials['username']
    );

    return $credentials;
  }

  /**
   * @param mixed $credentials
   * @param UserProviderInterface $userProvider
   * @param UserProviderInterface $userProvider
   *
   * @return User|object|UserInterface|null
   */
  public function getUser($credentials, UserProviderInterface $userProvider)
  {
    $token = new CsrfToken('authenticate', $credentials['csrf_token']);
    if (!$this->csrfTokenManager->isTokenValid($token)) {
      throw new InvalidCsrfTokenException();
    }

    $user = $this->entityManager->getRepository(User::class)->findOneBy(['username' => $credentials['username']]);

    if (!$user) {
      // fail authentication with a custom error
      throw new CustomUserMessageAuthenticationException($this->translator->trans('security.missing_username'));
    }
    if ($user instanceof OperatoreUser && $user->isSystemUser()) {
      // fail authentication api user
      throw new CustomUserMessageAuthenticationException($this->translator->trans('security.error_login_system_user'));
    }
    return $user;
  }

  /**
   * @param mixed $credentials
   * @param UserInterface $user
   *
   * @return bool
   */
  public function checkCredentials($credentials, UserInterface $user): bool
  {
    return $this->passwordEncoder->isPasswordValid($user, $credentials['password']);
  }

  /**
   * Used to upgrade (rehash) the user's password automatically over time.
   *
   * @param $credentials
   *
   * @return string|null
   */
  public function getPassword($credentials): ?string
  {
    return $credentials['password'];
  }

  /**
   * @param Request $request
   * @param TokenInterface $token
   * @param string $providerKey
   *
   * @return RedirectResponse|Response|null
   * @throws Exception
   */
  public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
  {
    /*if ($targetPath = $this->getTargetPath($request->getSession(), $providerKey)) {
      return new RedirectResponse($targetPath);
    }
    $user = $token->getUser();
    if ($user) {
      return new RedirectResponse($this->urlGenerator->generate('admin_index'));
    }
    return new RedirectResponse($this->getLoginUrl());
    */
    return null;
  }

  /**
   * @return string
   */
  protected function getLoginUrl()
  {
    return $this->urlGenerator->generate('security_login');
  }
}
