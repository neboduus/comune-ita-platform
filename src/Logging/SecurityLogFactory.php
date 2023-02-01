<?php

namespace App\Logging;

use App\Model\Security\SecurityLogInterface;
use App\Model\Security\User\AdminCreatedSecurityLog;
use App\Model\Security\User\AdminRemovedSecurityLog;
use App\Model\Security\User\LoginSuccessSecurityLog;
use App\Model\Security\User\LoginFailedSecurityLog;
use App\Model\Security\User\OperatorCreatedSecurityLog;
use App\Model\Security\User\OperatorRemovedecurityLog;
use App\Model\Security\User\ResetPasswordRequestSecurityLog;
use App\Model\Security\User\ResetPasswordSuccessSecurityLog;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\User\UserInterface;

class SecurityLogFactory
{

  const REQUEST_TIMEOUT = 1;
  private ?string $ipInfoWsUrl;
  private LoggerInterface $logger;
  // Nb: Il nome della variabile deve essere il nome del pool senza punto in camel case altrimenti non riesce a fare l'injection
  // Es. security.cache --> $securityCache
  private CacheItemPoolInterface $securityCache;

  public function __construct(?string $ipInfoWsUrl, CacheItemPoolInterface $securityCache, LoggerInterface $logger)
  {
    $this->ipInfoWsUrl = $ipInfoWsUrl;
    $this->logger = $logger;
    $this->securityCache = $securityCache;
  }

  /**
   * @throws \Exception|InvalidArgumentException
   */
  public function getSecurityLog($type, ?UserInterface $user, ?Request $request, $subject = null): SecurityLogInterface
  {

    // Todo: creare un registry o init in automatico
    switch ($type) {
      case SecurityLogInterface::ACTION_USER_LOGIN_SUCCESS:
        $securityLog = new LoginSuccessSecurityLog($user);
        break;

      case SecurityLogInterface::ACTION_USER_LOGIN_FAILED:
        $securityLog = new LoginFailedSecurityLog($user);
        break;

      case SecurityLogInterface::ACTION_USER_RESET_PASSWORD_REQUEST:
        $securityLog = new ResetPasswordRequestSecurityLog($user, $subject);
        break;

      case SecurityLogInterface::ACTION_USER_RESET_PASSWORD_SUCCESS:
        $securityLog = new ResetPasswordSuccessSecurityLog($user, $subject);
        break;

      case SecurityLogInterface::ACTION_USER_ADMIN_CREATED:
        $securityLog = new AdminCreatedSecurityLog($user, $subject);
        break;

      case SecurityLogInterface::ACTION_USER_ADMIN_REMOVED:
        $securityLog = new AdminRemovedSecurityLog($user, $subject);
        break;

      case SecurityLogInterface::ACTION_USER_OPERATOR_CREATED:
        $securityLog = new OperatorCreatedSecurityLog($user, $subject);
        break;

      case SecurityLogInterface::ACTION_USER_OPERATOR_REMOVED:
        $securityLog = new OperatorRemovedecurityLog($user, $subject);
        break;
    }

    if ($this->ipInfoWsUrl && $request instanceof Request && $request->server->has('HTTP_X_REAL_IP')) {
      $origin = $this->generateOrigin($request->server->get('HTTP_X_REAL_IP'));
      $securityLog->setOrigin($origin);
    }

    // Todo: creare metodo adhoc per settare la sorgente
    if ($request instanceof Request) {

      if (strpos($request->getRequestUri(), '/api/') == true) {
        $securityLog->setSource(SecurityLogInterface::SOURCE_API);
      }

      $securityLog->getActor()->setSessionId($request->getSession()->getId());
    } else {
      $securityLog->setSource(SecurityLogInterface::SOURCE_CLI);
    }

    $securityLog->generateShortDescription();
    $securityLog->generateMeta();
    return $securityLog;
  }

  /**
   * @throws \Exception
   * @throws InvalidArgumentException
   */
  private function generateOrigin(string $ip): ?array
  {

    try {
      $ipInfo = $this->securityCache->getItem($ip);
      if (!$ipInfo->isHit())
      {
        $ipInfo->set($this->fetchIpInfo($ip));
        $ipInfo->expiresAfter(86400);
        $this->securityCache->save($ipInfo);
      }

      $ipInfoValue = $ipInfo->get();

      $origin = [];
      $keys = ['ip', 'ip_decimal', 'country', 'country_iso', 'country_eu', 'region_name', 'region_code', 'city', 'asn', 'asn_org'];

      foreach ($keys as $k ) {
        if (isset($ipInfoValue[$k]) && !empty($ipInfoValue[$k])) {
          $origin[$k] = $ipInfoValue[$k];
        }
      }
      return $origin;
    } catch (\Exception $e) {
      return null;
    }
  }

  /**
   * @throws \Exception
   */
  private function fetchIpInfo(string $ip)
  {
    // Guzzle non funziona, da verificare
    $curl = curl_init();
    curl_setopt_array($curl, [
      CURLOPT_URL => $this->ipInfoWsUrl . "/json?ip=" . $ip,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => self::REQUEST_TIMEOUT,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "GET",
      CURLOPT_HTTPHEADER => [
        "Accept: application/json",
      ],
    ]);

    $response = curl_exec($curl);
    $err = curl_error($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    if ($err || $httpCode != 200) {
      throw new \Exception($err);
    } else {
      return json_decode($response, true);
    }
  }
}
