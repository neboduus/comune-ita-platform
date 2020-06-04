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
    // Prosegue se
    return  $request->attributes->get('_route') === 'login' && ( $this->checkShibbolethUserData($request) || $request->query->has( self::KEY_PARAMETER_NAME ) );
  }

  public function getCredentials(Request $request)
  {
    $credentials = $this->createUserDataFromRequest($request);

    if ($credentials[self::KEY_PARAMETER_NAME] === null) {
      return null;
    }

    $session = $request->getSession();
    $session->set(
      Security::LAST_USERNAME,
      $credentials[self::KEY_PARAMETER_NAME]
    );

    $session->set('user_data', $credentials);

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
   * @param Request $request
   * @param AuthenticationException|null $authException
   * @return JsonResponse|Response
   */
  public function start(Request $request, AuthenticationException $authException = null)
  {
    $data = ['message' => 'Authentication Required'];
    return new JsonResponse($data, Response::HTTP_UNAUTHORIZED);
  }

  public function checkCredentials($credentials, UserInterface $user)
  {
    // TODO: Implement checkCredentials() method.
    //dump('checkCredentials');
    //exit;

    return true;

  }

  public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
  {
    // TODO: Implement onAuthenticationFailure() method.
    dump('onAuthenticationFailure');
    exit;
  }

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

    // Adesso è get poi sarà headers
    if ( $data[self::KEY_PARAMETER_NAME] == null) {
      $data[self::KEY_PARAMETER_NAME] = $request->query->get(self::KEY_PARAMETER_NAME);
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
