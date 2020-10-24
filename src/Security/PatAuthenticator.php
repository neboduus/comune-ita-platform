<?php

namespace App\Security;

use App\Entity\CPSUser;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Security;

class PatAuthenticator extends AbstractAuthenticator
{
  private $shibboletServerVarNames;

  /** @var Security */
  private $security;

  /**
   * OpenLoginAuthenticator constructor.
   * @param UrlGeneratorInterface $urlGenerator
   * @param array $shibboletServerVarNames
   * @param $loginRoute
   * @param Security $security
   */
  public function __construct(UrlGeneratorInterface $urlGenerator, $shibboletServerVarNames, $loginRoute, Security $security)
  {
    $this->urlGenerator = $urlGenerator;
    $this->shibboletServerVarNames = $shibboletServerVarNames;
    $this->loginRoute = $loginRoute;
    $this->security = $security;
  }

  protected function getLoginRouteSupported()
  {
    return ['login_pat'];
  }

  public function supports(Request $request)
  {
    try {
      $this->checkLoginRoute();
    } catch (\Exception $e) {
      return false;
    }
    $credential = $this->getCredentials($request);
    $user = $this->security->getUser();
    if (
      $user instanceof CPSUser
      && isset($credential['codiceFiscale'])
      && strtolower($user->getCodiceFiscale()) == strtolower($credential['codiceFiscale'])
    ) {
      return false;
    }
    return $request->attributes->get('_route') === 'login_pat' && $this->checkShibbolethUserData($request);
  }

  /**
   * @param Request $request
   * @return bool
   *
   * Check if at least one shibboleth parameter is present
   */
  private function checkShibbolethUserData(Request $request)
  {
    $userDataKeys = array_flip($this->shibboletServerVarNames);
    $serverProps = $request->server->all();
    foreach ($userDataKeys as $shibbKey => $ourKey) {
      if (isset($serverProps[$shibbKey])) {
        return true;
      }
    }

    return false;
  }

  /**
   * @param Request $request
   * @param $userDataKeys
   * @return array
   */
  protected function createUserDataFromRequest(Request $request)
  {
    $userDataKeys = array_flip($this->shibboletServerVarNames);
    $serverProps = $request->server->all();
    $data = [];
    foreach ($userDataKeys as $shibbKey => $ourKey) {
      $data[$ourKey] = isset($serverProps[$shibbKey]) ? $serverProps[$shibbKey] : null;
    }

    // Fallback on session
    if ($data[self::KEY_PARAMETER_NAME] == null) {
      $data = $request->getSession()->get('user_data');
    }

    return $data;
  }

}
