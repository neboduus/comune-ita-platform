<?php


namespace AppBundle\Security;


use AppBundle\Entity\CPSUser;
use AppBundle\Entity\User;
use AppBundle\Services\CPSUserProvider;
use Doctrine\ORM\EntityManagerInterface;
use \Exception;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class OpenLoginAuthenticator extends AbstractGuardAuthenticator
{
  use TargetPathTrait;

  const KEY_PARAMETER_NAME = 'codiceFiscale';

  /**
   * @var UrlGeneratorInterface
   */
  private $urlGenerator;


  /**
   * OpenLoginAuthenticator constructor.
   * @param UrlGeneratorInterface $urlGenerator
   * @param array $shibboletServerVarnames
   */
  public function __construct( UrlGeneratorInterface $urlGenerator)
  {
    $this->urlGenerator = $urlGenerator;
  }

  public function supports(Request $request)
  {
    // Prosegue se...
    return  $request->attributes->get('_route') === 'login_open' && $this->checkHeaderUserData($request);
  }

  public function getCredentials(Request $request)
  {
    $credentials = $this->createUserDataFromRequest($request);

    if ($credentials[self::KEY_PARAMETER_NAME] === null) {
      return null;
    }

    return $credentials;
  }

  public function getUser($credentials, UserProviderInterface $userProvider)
  {

    if ($userProvider instanceof CPSUserProvider) {
      return $userProvider->provideUser($credentials);
    }
    throw new \InvalidArgumentException(
      sprintf("UserProvider for CPSAuthenticator must be a %s instance", CPSUserProvider::class)
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
    //return false;
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
   * @param $userDataKeys
   * @return array
   */
  private function createUserDataFromRequest(Request $request)
  {
    //codiceFiscale, cognome, nome, emailAddress, spidCode

    $data[self::KEY_PARAMETER_NAME] = $request->headers->get('x-forwarded-user-fiscalnumber');
    $data['cognome'] = $request->headers->get('x-forwarded-user-familyname');
    $data['nome'] = $request->headers->get('x-forwarded-user-name');
    $data['emailAddress'] = $request->headers->get('x-forwarded-user-email');
    $data['spidCode'] = $request->headers->get('x-forwarded-user');

    // Fallback on session
    if ( $data[self::KEY_PARAMETER_NAME] == null) {
      $data = $request->getSession()->get('user_data');
    }

    return $data;
  }

  /**
   * @param Request $request
   * @return bool
   *
   * Check if at least one shibboleth parameter is present
   */
  private function checkHeaderUserData(Request $request)
  {

    $headers = ['x-forwarded-user-provider', 'x-forwarded-user-name', 'x-forwarded-user-fiscalnumber', 'x-forwarded-user-familyname',
               'x-forwarded-user-email', 'x-forwarded-user'];

    foreach ($headers as $key) {
      if (!$request->headers->has($key)) {
        return false;
      }
    }
    return true;
  }
}
