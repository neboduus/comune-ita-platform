<?php

namespace AppBundle\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class PatAuthenticator extends AbstractAuthenticator
{
  private $shibboletServerVarNames;

  /**
   * OpenLoginAuthenticator constructor.
   * @param UrlGeneratorInterface $urlGenerator
   * @param array $shibboletServerVarNames
   */
  public function __construct(UrlGeneratorInterface $urlGenerator, $shibboletServerVarNames, $loginRoute)
  {
    $this->urlGenerator = $urlGenerator;
    $this->shibboletServerVarNames = $shibboletServerVarNames;
    $this->loginRoute = $loginRoute;
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
