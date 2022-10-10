<?php

namespace App\Security;

use App\Dto\UserAuthenticationData;
use App\Entity\CPSUser;
use App\Services\InstanceService;
use App\Services\UserSessionService;
use Artprima\PrometheusMetricsBundle\Metrics\MetricsCollectorInterface;
use Doctrine\ORM\EntityManagerInterface;
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

class TokenAuthenticator extends AbstractAuthenticator
{
  const LOGIN_ROUTE = 'login_token';

  const QUERY_TOKEN_PARAMETER = 'token';

  /**
   * @var InstanceService
   */
  private $instanceService;

  /**
   * @var MetricsCollectorInterface
   */
  private $userMetrics;

  private $userdata = [];
  /**
   * @var LoggerInterface
   */
  private $logger;
  /**
   * @var EntityManagerInterface
   */
  private $entityManager;

  /**
   * OpenLoginAuthenticator constructor.
   * @param UrlGeneratorInterface $urlGenerator
   * @param $loginRoute
   * @param UserSessionService $userSessionService
   * @param InstanceService $instanceService
   * @param MetricsCollectorInterface $userMetrics
   * @param JWTTokenManagerInterface $JWTTokenManager
   * @param LoggerInterface $logger
   */
  public function __construct(
    UrlGeneratorInterface $urlGenerator,
    $loginRoute,
    UserSessionService $userSessionService,
    InstanceService $instanceService,
    MetricsCollectorInterface $userMetrics,
    JWTTokenManagerInterface $JWTTokenManager,
    LoggerInterface $logger,
    EntityManagerInterface $entityManager

  )
  {
    $this->urlGenerator = $urlGenerator;
    $this->loginRoute = $loginRoute;
    $this->userSessionService = $userSessionService;
    $this->instanceService = $instanceService;
    $this->userMetrics = $userMetrics;
    $this->JWTTokenManager = $JWTTokenManager;
    $this->logger = $logger;
    $this->entityManager = $entityManager;
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
    return $request->attributes->get('_route') === self::LOGIN_ROUTE && $request->get(self::QUERY_TOKEN_PARAMETER);
  }

  /**
   * @param Request $request
   * @return array
   */
  protected function createUserDataFromRequest(Request $request): ?array
  {

    $userRepo = $this->entityManager->getRepository('App\Entity\CPSUser');
    $user = $userRepo->findOneBy(['confirmationToken' => $request->query->get('token')]);

    // Todo: verificare esistenza token e fetch utente
    if ($user instanceof CPSUser) {
      $this->userdata = [
        'nome' => $user->getNome(),
        'cognome' => $user->getCognome(),
        'codiceFiscale' => $user->getCodiceFiscale()
      ];
      return $this->userdata;
    }

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
      'authenticationMethod' => CPSUser::IDP_NONE,
      'sessionId' => '',
      'spidCode' => '',
      'spidLevel' => '',
      'instant' => $dateTimeObject->format(DATE_ISO8601),
      'sessionIndex' => '',
    ];

    //$this->userMetrics->incLoginSuccess($this->instanceService->getCurrentInstance()->getSlug(), 'login-open', $data['authenticationMethod'], $data['spidLevel']);

    return UserAuthenticationData::fromArray($data);
  }
}
