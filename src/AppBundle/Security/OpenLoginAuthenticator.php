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
    if ($request->getSession()->has(Security::LAST_USERNAME)) {
      return false;
    }
    // TODO: Check if there are parameters in env variables or Request header
    return !empty($this->createUserDataFromRequest($request, $this->shibboletServerVarnames)) || $request->query->has('codiceFiscale');
  }

  public function getCredentials(Request $request)
  {
    $userDataKeys = array_flip($this->shibboletServerVarnames);
    $credentials = $this->createUserDataFromRequest($request, $userDataKeys);

    if ($credentials["codiceFiscale"] === null) {
      return null;
    }

    $request->getSession()->set(
      Security::LAST_USERNAME,
      $credentials["codiceFiscale"]
    );
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
    /*if ($targetPath = $this->getTargetPath($request->getSession(), $providerKey)) {
      return new RedirectResponse($targetPath);
    }*/

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
  private function createUserDataFromRequest(Request $request, $userDataKeys)
  {
    $serverProps = $request->server->all();
    $data = [];
    foreach ($userDataKeys as $shibbKey => $ourKey) {
      $data[$ourKey] = isset($serverProps[$shibbKey]) ? $serverProps[$shibbKey] : null;
    }

    if (empty($data)) {
      $data = ['codiceFiscale' => $request->query->get('codiceFiscale')];
    }
    return $data;
  }

}
