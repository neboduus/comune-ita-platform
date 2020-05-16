<?php

namespace AppBundle\Security;


use AppBundle\Entity\CPSUser;
use AppBundle\Services\CPSUserProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;

/**
 * Class CPSAuthenticator
 * @package AppBundle\Security
 */
class CPSAuthenticator extends AbstractGuardAuthenticator
{

  private $shibboletServerVarnames;

  /**
   * @var array
   */
  //TODO:test this mapping
  public function __construct($shibboletServerVarnames)
  {
    $this->shibboletServerVarnames = $shibboletServerVarnames;
  }

  /**
   * @param Request $request
   * @param AuthenticationException|null $authException
   * @return Response
   */
  public function start(Request $request, AuthenticationException $authException = null)
  {
    return new Response('Authentication Required', 401);
  }

  /**
   * @param Request $request
   * @return array|null
   */
  public function getCredentials(Request $request)
  {
    $userDataKeys = array_flip($this->shibboletServerVarnames);
    $data = self::createUserDataFromRequest($request, $userDataKeys);
    if ($data["codiceFiscale"] === null) {
      return null;
    }

    return $data;
  }

  private static function createUserDataFromRequest(Request $request, $userDataKeys)
  {
    $serverProps = $request->server->all();
    $data = [];
    foreach ($userDataKeys as $shibbKey => $ourKey) {
      $shibbKeys = explode('|', $shibbKey);
      foreach ($shibbKeys as $sKey){
        if (isset($serverProps[$sKey])){
          $data[$ourKey] = $serverProps[$sKey];
        }
      }
    }

    return $data;
  }

  /**
   * @param mixed $credentials
   * @param UserProviderInterface $userProvider
   *
   * @return CPSUser
   * @throws \InvalidArgumentException
   */
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
   * @param mixed $credentials
   * @param UserInterface $user
   * @return bool
   */
  public function checkCredentials($credentials, UserInterface $user)
  {
    return true;
  }

  /**
   * @param Request $request
   * @param AuthenticationException $exception
   * @return Response
   */
  public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
  {
    $message = strtr($exception->getMessageKey(), $exception->getMessageData());

    return new Response($message, 403);
  }

  /**
   * @param Request $request
   * @param TokenInterface $token
   * @param string $providerKey
   * @return null
   */
  public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
  {
    return null;
  }

  /**
   * @inheritdoc
   * @return bool
   */
  public function supportsRememberMe()
  {
    return false;
  }
}
