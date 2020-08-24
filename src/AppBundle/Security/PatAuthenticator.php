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

class PatAuthenticator extends AbstractGuardAuthenticator
{
  use TargetPathTrait;

  const KEY_PARAMETER_NAME = 'codiceFiscale';

  /**
   * @var UrlGeneratorInterface
   */
  private $urlGenerator;

  private $shibboletServerVarnames;

  /**
   * OpenLoginAuthenticator constructor.
   * @param UrlGeneratorInterface $urlGenerator
   * @param array $shibboletServerVarnames
   */
  public function __construct( UrlGeneratorInterface $urlGenerator, $shibboletServerVarnames )
  {
    $this->urlGenerator = $urlGenerator;
    $this->shibboletServerVarnames = $shibboletServerVarnames;
  }

  public function supports(Request $request)
  {
    // Prosegue se...
    return  $request->attributes->get('_route') === 'login_pat' && $this->checkShibbolethUserData($request);
  }

  public function getCredentials(Request $request)
  {
    $credentials = $this->createUserDataFromRequest($request);

    return $credentials;
  }

  public function getUser($credentials, UserProviderInterface $userProvider)
  {
    /*$user = $this->entityManager->getRepository(User::class)->findOneBy(['username' => $credentials['username']]);

    if (!$user) {
      // fail authentication with a custom error
      throw new Exception("User with Username {$credentials['username']} could not be found.");
    }*/

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
    // Shibd
    $userDataKeys = array_flip($this->shibboletServerVarnames);
    $serverProps = $request->server->all();
    $data = [];
    foreach ($userDataKeys as $shibbKey => $ourKey) {
      $data[$ourKey] = isset($serverProps[$shibbKey]) ? $serverProps[$shibbKey] : null;
    }

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
  private function checkShibbolethUserData(Request $request)
  {
    $userDataKeys = array_flip($this->shibboletServerVarnames);
    $serverProps = $request->server->all();
    foreach ($userDataKeys as $shibbKey => $ourKey) {
      if (isset($serverProps[$shibbKey])) {
        return true;
      }
    }
    return false;
  }
}
