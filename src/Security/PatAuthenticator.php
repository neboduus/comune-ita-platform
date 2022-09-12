<?php

namespace App\Security;

use App\Dto\UserAuthenticationData;
use App\Entity\CPSUser;
use App\Services\InstanceService;
//use App\Services\Metrics\UserMetrics;
use App\Services\UserSessionService;
//use Artprima\PrometheusMetricsBundle\Metrics\MetricsCollectorInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
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
   * OpenLoginAuthenticator constructor.
   * @param UrlGeneratorInterface $urlGenerator
   * @param array $shibboletServerVarNames
   * @param $loginRoute
   * @param UserSessionService $userSessionService
   * @param InstanceService $instanceService
   * @param JWTTokenManagerInterface $JWTTokenManager
   */
  public function __construct(
    UrlGeneratorInterface $urlGenerator,
    $shibboletServerVarNames,
    $loginRoute,
    UserSessionService $userSessionService,
    InstanceService $instanceService,
    JWTTokenManagerInterface $JWTTokenManager
  ) {
    $this->urlGenerator = $urlGenerator;
    $this->shibboletServerVarNames = $shibboletServerVarNames;
    $this->loginRoute = $loginRoute;
    $this->userSessionService = $userSessionService;
    // TODO: riabilitare le metriche nella classe appena soddisfatte le altre dipendenze di Prometheus
    // $this->userMetrics = $userMetrics;
    $this->instanceService = $instanceService;
    $this->JWTTokenManager = $JWTTokenManager;
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

    $spid = [];

    $request->server->add($spid);

    if ( !$request->server->get($this->shibboletServerVarNames['shibSessionId']) ||
         !$request->server->get($this->shibboletServerVarNames['shibAuthenticationIstant']) ||
         !$request->server->get($this->shibboletServerVarNames['shibSessionIndex'])
    ) {
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

  /**
   * @param Request $request
   * @param UserInterface $user
   * @return UserAuthenticationData
   * @throws \Exception
   */
  protected function getUserAuthenticationData(Request $request, UserInterface $user)
  {
    $data = [];
    // Spid
    if (
      $request->server->has($this->shibboletServerVarNames['spidCode']) &&
      !empty($request->server->get($this->shibboletServerVarNames['spidCode']))
    ) {
      $data = [
        'authenticationMethod' => CPSUser::IDP_SPID,
        'sessionId' => $request->server->get($this->shibboletServerVarNames['shibSessionId']),
        'spidCode' => $request->server->get($this->shibboletServerVarNames['spidCode']),
        'instant' => $request->server->get($this->shibboletServerVarNames['shibAuthenticationIstant']),
        'sessionIndex' => $request->server->get($this->shibboletServerVarNames['shibSessionIndex']),
        'spidLevel' => $request->server->get($this->shibboletServerVarNames['spidLevel'] ?? ''),
      ];

      //$this->userMetrics->incLoginSuccess($this->instanceService->getCurrentInstance()->getSlug(), 'login-pat', $data['authenticationMethod'], $data['spidLevel']);
      return UserAuthenticationData::fromArray($data);
    }

    // Cps
    if (
      $request->server->get($this->shibboletServerVarNames['x509certificate_issuerdn']) &&
      !empty($request->server->get($this->shibboletServerVarNames['x509certificate_issuerdn'])) &&
      $request->server->get($this->shibboletServerVarNames['x509certificate_subjectdn']) &&
      !empty($request->server->get($this->shibboletServerVarNames['x509certificate_subjectdn'])) &&
      $request->server->get($this->shibboletServerVarNames['x509certificate_base64']) &&
      !empty($request->server->get($this->shibboletServerVarNames['x509certificate_base64']))
    ) {
      $data = [
        'authenticationMethod' => CPSUser::IDP_CPS_OR_CNS,
        'sessionId' => $request->server->get($this->shibboletServerVarNames['shibSessionId']),
        'certificateIssuer' => $request->server->get($this->shibboletServerVarNames['x509certificate_issuerdn']),
        'certificateSubject' => $request->server->get($this->shibboletServerVarNames['x509certificate_subjectdn']),
        'certificate' => $request->server->get($this->shibboletServerVarNames['x509certificate_base64']),
        'instant' => $request->server->get($this->shibboletServerVarNames['shibAuthenticationIstant']),
        'sessionIndex' => $request->server->get($this->shibboletServerVarNames['shibSessionIndex'])
      ];

      //$this->userMetrics->incLoginSuccess($this->instanceService->getCurrentInstance()->getSlug(), 'login-pat', $data['authenticationMethod'], '');
      return UserAuthenticationData::fromArray($data);
    }

    // Cie
    if ( $request->server->has($this->shibboletServerVarNames['shibAuthnContextClass'])
         && $request->server->get($this->shibboletServerVarNames['shibAuthnContextClass']) == 'urn:oasis:names:tc:SAML:2.0:ac:classes:Smartcard'
         && (!$request->server->has($this->shibboletServerVarNames['spidCode']) || empty($request->server->get($this->shibboletServerVarNames['spidCode'])))
         && (!$request->server->has($this->shibboletServerVarNames['x509certificate_base64']) || empty($request->server->get($this->shibboletServerVarNames['x509certificate_base64'])))
      ) {

      $data = [
        'authenticationMethod' => CPSUser::IDP_CIE,
        'sessionId' => $request->server->get($this->shibboletServerVarNames['shibSessionId']),
        'instant' => $request->server->get($this->shibboletServerVarNames['shibAuthenticationIstant']),
        'sessionIndex' => $request->server->get($this->shibboletServerVarNames['shibSessionIndex']),
      ];

      //$this->userMetrics->incLoginSuccess($this->instanceService->getCurrentInstance()->getSlug(), 'login-pat', $data['authenticationMethod'], '');
      return UserAuthenticationData::fromArray($data);
    }

    throw new \Exception('PatAuthenticator:getUserAuthenticationData - insufficient authentication data');
  }
}
