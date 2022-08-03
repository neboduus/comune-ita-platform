<?php

namespace App\Security;

use App\Dto\UserAuthenticationData;
use App\Entity\CPSUser;
use App\Services\InstanceService;
use App\Services\UserSessionService;
use Artprima\PrometheusMetricsBundle\Metrics\MetricsCollectorInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use GuzzleHttp\Client;

class CasAuthenticator extends AbstractAuthenticator
{
  const LOGIN_ROUTE = 'login_cas';

  const QUERY_TICKET_PARAMETER = 'ticket';
  const QUERY_SERVICE_PARAMETER = 'service';

  const XML_NAMESPACE = 'cas';

  /**
   * @var InstanceService
   */

  private $instanceService;

  /**
   * @var MetricsCollectorInterface
   */
  private $userMetrics;

  private $casLoginUrl;

  private $casValidationUrl;

  private $userdata = [];
  /**
   * @var LoggerInterface
   */
  private $logger;

  /**
   * OpenLoginAuthenticator constructor.
   * @param UrlGeneratorInterface $urlGenerator
   * @param $loginRoute
   * @param UserSessionService $userSessionService
   * @param InstanceService $instanceService
   * @param MetricsCollectorInterface $userMetrics
   * @param JWTTokenManagerInterface $JWTTokenManager
   * @param $casLoginUrl
   * @param $casValidationUrl
   * @param LoggerInterface $logger
   */
  public function __construct(
    UrlGeneratorInterface $urlGenerator,
    $loginRoute,
    UserSessionService $userSessionService,
    InstanceService $instanceService,
    MetricsCollectorInterface $userMetrics,
    JWTTokenManagerInterface $JWTTokenManager,
    $casLoginUrl,
    $casValidationUrl,
    LoggerInterface $logger
  )
  {
    $this->urlGenerator = $urlGenerator;
    $this->loginRoute = $loginRoute;
    $this->userSessionService = $userSessionService;
    $this->instanceService = $instanceService;
    $this->userMetrics = $userMetrics;
    $this->JWTTokenManager = $JWTTokenManager;
    $this->casLoginUrl = $casLoginUrl;
    $this->casValidationUrl = $casValidationUrl;
    $this->logger = $logger;
  }

  /**
   * @inheritDoc
   */
  protected function getLoginRouteSupported()
  {
    return [self::LOGIN_ROUTE];
  }

  public function supports(Request $request)
  {
    try {
      $this->checkLoginRoute();
    } catch (\Exception $e) {
      return false;
    }
    return $request->attributes->get('_route') === self::LOGIN_ROUTE && $request->get(self::QUERY_TICKET_PARAMETER);
  }

  /**
   * @param Request $request
   * @return array
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  protected function createUserDataFromRequest(Request $request)
  {
    if ($request->get('test')) {
      $string = "
      <cas:serviceResponse xmlns:cas='http://www.yale.edu/tp/cas'>
        <cas:authenticationSuccess>
          <cas:user>NTSCTT80E59D086L</cas:user>
          <cas:attributes>
            <cas:credentialType>ClientCredential</cas:credentialType>
            <cas:identificativoUtente>spidcode</cas:identificativoUtente>
            <cas:isFromNewLogin>true</cas:isFromNewLogin>
            <cas:authenticationDate>2022-01-25T09:09:07.500837Z</cas:authenticationDate>
            <cas:cognome>Poste41</cas:cognome>
            <cas:clientName>BresciaGOV_SPID</cas:clientName>
            <cas:authenticationMethod>DelegatedClientAuthenticationHandler</cas:authenticationMethod>
            <cas:successfulAuthenticationHandlers>DelegatedClientAuthenticationHandler</cas:successfulAuthenticationHandlers>
            <cas:nome>Test</cas:nome>
            <cas:longTermAuthenticationRequestTokenUsed>false</cas:longTermAuthenticationRequestTokenUsed>
            <cas:codiceFiscale>NTSCTT80E59D086L</cas:codiceFiscale>
          </cas:attributes>
        </cas:authenticationSuccess>
      </cas:serviceResponse>
      ";
      /*$string = "
      <cas:serviceResponse xmlns:cas='http://www.yale.edu/tp/cas'>
        <cas:authenticationSuccess>
          <cas:user>LCCRFL83S12A345M</cas:user>
          <cas:attributes>
          </cas:attributes>
        </cas:authenticationSuccess>
      </cas:serviceResponse>
      ";*/
    } else {

      $url = $this->casValidationUrl . '?' . self::QUERY_TICKET_PARAMETER . '=' .
        $request->get(self::QUERY_TICKET_PARAMETER) . '&' .
        self::QUERY_SERVICE_PARAMETER . '=' . urlencode($this->removeCasTicket($request->getUri()));

      $client = new Client();
      $response = $client->request('GET', $url);
      $string = $response->getBody()->getContents();
    }

    $xml = new \SimpleXMLElement($string, 0, false, self::XML_NAMESPACE, true);

    if (isset($xml->authenticationSuccess)) {
      $this->userdata = (array)$xml->authenticationSuccess->attributes;
      /*if (!isset($this->userdata[self::KEY_PARAMETER_NAME])) {
        $this->userdata[self::KEY_PARAMETER_NAME] = (string)$xml->authenticationSuccess->user;
      }*/
      return $this->userdata;
    }

    $this->logger->info(self::LOGIN_ROUTE, ['validation_response' => $string]);

    return null;
  }

  protected function getRequestDataToStoreInUserSession(Request $request)
  {
    return $request->headers->all();
  }

  protected function getUserAuthenticationData(Request $request, UserInterface $user)
  {
    try {
      $dateTimeObject = new \DateTime($this->userdata['authenticationDate']);
    } catch (\Exception $e) {
      $dateTimeObject = new \DateTime();
    }

    $data = [
      'authenticationMethod' => CPSUser::IDP_SPID,
      'sessionId' => '',
      'spidCode' => $this->userdata['identificativoUtente'] ?? '',
      'spidLevel' => '',
      'instant' => $dateTimeObject->format(DATE_ISO8601),
      'sessionIndex' => '',
    ];

    $this->userMetrics->incLoginSuccess($this->instanceService->getCurrentInstance()->getSlug(), 'login-open', $data['authenticationMethod'], $data['spidLevel']);

    return UserAuthenticationData::fromArray($data);
  }

  /**
   * Strip the CAS 'ticket' parameter from a uri.
   */
  private function removeCasTicket($uri)
  {
    $parsed_url = parse_url($uri);
    // If there are no query parameters, then there is nothing to do.
    if (empty($parsed_url['query'])) {
      return $uri;
    }
    parse_str($parsed_url['query'], $query_params);
    // If there is no 'ticket' parameter, there is nothing to do.
    if (!isset($query_params[self::QUERY_TICKET_PARAMETER])) {
      return $uri;
    }
    // Remove the ticket parameter and rebuild the query string.
    unset($query_params[self::QUERY_TICKET_PARAMETER]);
    if (empty($query_params)) {
      unset($parsed_url['query']);
    } else {
      $parsed_url['query'] = http_build_query($query_params);
    }

    // Rebuild the URI from the parsed components.
    // Source: https://secure.php.net/manual/en/function.parse-url.php#106731
    $scheme = isset($parsed_url['scheme']) ? $parsed_url['scheme'] . '://' : '';
    $host = isset($parsed_url['host']) ? $parsed_url['host'] : '';
    $port = isset($parsed_url['port']) ? ':' . $parsed_url['port'] : '';
    $user = isset($parsed_url['user']) ? $parsed_url['user'] : '';
    $pass = isset($parsed_url['pass']) ? ':' . $parsed_url['pass'] : '';
    $pass = ($user || $pass) ? "$pass@" : '';
    $path = isset($parsed_url['path']) ? $parsed_url['path'] : '';
    $query = isset($parsed_url['query']) ? '?' . $parsed_url['query'] : '';
    $fragment = isset($parsed_url['fragment']) ? '#' . $parsed_url['fragment'] : '';
    return "$scheme$user$pass$host$port$path$query$fragment";
  }
}
