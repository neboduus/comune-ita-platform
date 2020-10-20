<?php

namespace AppBundle\Security;

use AppBundle\Dto\UserAuthenticationData;
use AppBundle\Entity\Ente;
use AppBundle\Services\CPSUserProvider;
use AppBundle\Services\UserSessionService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

abstract class AbstractAuthenticator extends AbstractGuardAuthenticator
{
  use TargetPathTrait;

  const LOGIN_TYPE_NONE = 'none';

  const KEY_PARAMETER_NAME = 'codiceFiscale';

  /**
   * @var UrlGeneratorInterface
   */
  protected $urlGenerator;

  protected $loginRoute;

  /**
   * @var UserSessionService
   */
  protected $userSessionService;

  /**
   * @return string[]
   */
  abstract protected function getLoginRouteSupported();

  public function getCredentials(Request $request)
  {
    $credentials = $this->createUserDataFromRequest($request);

    if ($credentials[self::KEY_PARAMETER_NAME] === null) {
      return null;
    }

    return $credentials;
  }

  /**
   * @param Request $request
   * @return array
   */
  abstract protected function createUserDataFromRequest(Request $request);

  /**
   * @param Request $request
   * @return array
   */
  abstract protected function getRequestDataToStoreInUserSession(Request $request);

  /**
   * @param Request $request
   * @param UserInterface $user
   * @return UserAuthenticationData
   */
  abstract protected function getUserAuthenticationData(Request $request, UserInterface $user);

  public function getUser($credentials, UserProviderInterface $userProvider)
  {
    if ($userProvider instanceof CPSUserProvider) {
      return $userProvider->provideUser($credentials);
    }
    throw new \InvalidArgumentException(
      sprintf("UserProvider must be a %s instance", CPSUserProvider::class)
    );
  }

  /**
   * @param $credentials
   * @param UserInterface $user
   * @return bool
   */
  public function checkCredentials($credentials, UserInterface $user)
  {
    return true;
  }

  /**
   * @param Request $request
   * @param AuthenticationException|null $authException
   * @return JsonResponse|Response
   */
  public function start(Request $request, AuthenticationException $authException = null)
  {
    $url = $this->getLoginUrl();

    return new RedirectResponse($url);
  }

  /**
   * Return correct login route
   */
  private function getLoginUrl()
  {
    return $this->urlGenerator->generate('login');
  }

  /**
   * @param Request $request
   * @param AuthenticationException $exception
   * @return RedirectResponse|Response|null
   */
  public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
  {
    $url = $this->getLoginUrl();

    return new RedirectResponse($url);
  }

  /**
   * @param Request $request
   * @param TokenInterface $token
   * @param string $providerKey
   * @return RedirectResponse|Response|null
   */
  public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
  {
    $this->userSessionService->createCurrentUserSessionData(
      $token->getUser(),
      $this->getRequestDataToStoreInUserSession($request),
      $this->getUserAuthenticationData($request, $token->getUser())
    );
    if ($targetPath = $this->getTargetPath($request->getSession(), $providerKey)) {
      return new RedirectResponse($targetPath);
    }

    return new RedirectResponse($this->urlGenerator->generate('user_dashboard'));
  }

  /**
   * @return bool
   */
  public function supportsRememberMe()
  {
    return false;
  }

  /**
   * @throws \Exception
   */
  protected function checkLoginRoute()
  {
    if ($this->loginRoute == self::LOGIN_TYPE_NONE) {
      throw new \Exception('Login type none configured.');
    }

    if (!in_array($this->loginRoute, $this->getLoginRouteSupported())) {
      throw new \Exception('Authenticator does not match with configured login type.');
    }
  }
}
