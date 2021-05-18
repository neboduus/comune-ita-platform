<?php

namespace AppBundle\Security;

use AppBundle\Dto\UserAuthenticationData;
use AppBundle\Entity\CPSUser;
use AppBundle\Services\InstanceService;
use AppBundle\Services\Metrics\UserMetrics;
use AppBundle\Services\UserSessionService;
use Artprima\PrometheusMetricsBundle\Metrics\MetricsGeneratorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class PatAuthenticator extends AbstractAuthenticator
{
  private $shibboletServerVarNames;

  /**
   * @var InstanceService
   */
  private $instanceService;

  /**
   * @var UserMetrics
   */
  private $userMetrics;

  /**
   * OpenLoginAuthenticator constructor.
   * @param UrlGeneratorInterface $urlGenerator
   * @param array $shibboletServerVarNames
   * @param $loginRoute
   * @param UserSessionService $userSessionService
   * @param InstanceService $instanceService
   * @param MetricsGeneratorInterface $userMetrics
   */
  public function __construct(
    UrlGeneratorInterface $urlGenerator,
    $shibboletServerVarNames,
    $loginRoute,
    UserSessionService $userSessionService,
    InstanceService $instanceService,
    MetricsGeneratorInterface $userMetrics
  ) {
    $this->urlGenerator = $urlGenerator;
    $this->shibboletServerVarNames = $shibboletServerVarNames;
    $this->loginRoute = $loginRoute;
    $this->userSessionService = $userSessionService;
    $this->userMetrics = $userMetrics;
    $this->instanceService = $instanceService;
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
   * Check if at least one among spidcode or all x509 certificate shibboleth parameters are present
   */
  private function checkShibbolethUserData(Request $request)
  {
    if (!$request->server->get($this->shibboletServerVarNames['spidCode']) && !(
        $request->server->get($this->shibboletServerVarNames['x509certificate_issuerdn']) &&
        $request->server->get($this->shibboletServerVarNames['x509certificate_subjectdn']) &&
        $request->server->get($this->shibboletServerVarNames['x509certificate_base64'])
      )) {
      return false;
    }
    return true;
  }

  protected function getLoginRouteSupported()
  {
    return ['login_pat'];
  }

  protected function getRequestDataToStoreInUserSession(Request $request)
  {
    return $this->createUserDataFromRequest($request);
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

  protected function getUserAuthenticationData(Request $request, UserInterface $user)
  {
    if ($request->server->has($this->shibboletServerVarNames['spidCode'])) {
      $data = [
        'authenticationMethod' => CPSUser::IDP_SPID,
        'sessionId' => $request->server->get($this->shibboletServerVarNames['shibSessionId']),
        'spidCode' => $request->server->get($this->shibboletServerVarNames['spidCode']),
        'instant' => $request->server->get($this->shibboletServerVarNames['shibAuthenticationIstant']),
        'sessionIndex' => $request->server->get($this->shibboletServerVarNames['shibSessionIndex']),
        'spidLevel' => $request->server->get($this->shibboletServerVarNames['spidLevel'] ?? ''),
      ];
    } else {
      $data = [
        'authenticationMethod' => CPSUser::IDP_CPS_OR_CNS,
        'sessionId' => $request->server->get($this->shibboletServerVarNames['shibSessionId']),
        'certificateIssuer' => $request->server->get($this->shibboletServerVarNames['x509certificate_issuerdn']),
        'certificateSubject' => $request->server->get($this->shibboletServerVarNames['x509certificate_subjectdn']),
        'certificate' => $request->server->get($this->shibboletServerVarNames['x509certificate_base64']),
        'instant' => $request->server->get($this->shibboletServerVarNames['shibAuthenticationIstant']),
        'sessionIndex' => $request->server->get($this->shibboletServerVarNames['shibSessionIndex']),
        'spidLevel' => $request->server->get($this->shibboletServerVarNames['spidLevel']),
      ];
    }

    try {
      $this->userMetrics->incLoginSuccess($this->instanceService->getCurrentInstance()->getSlug(), 'login-pat', $data['authenticationMethod'], $data['spidLevel']);
    } catch (\Exception $e) {
      // todo: add logger
    }


    return UserAuthenticationData::fromArray($data);
  }
}
