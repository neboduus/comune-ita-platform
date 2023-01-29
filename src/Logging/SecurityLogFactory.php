<?php

namespace App\Logging;

use App\Model\Security\Origin;
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

    if ($this->ipInfoWsUrl && $request instanceof Request && $request->server->has('REMOTE_ADDR')) {
      $origin = $this->generateOrigin($request->server->get('REMOTE_ADDR'));
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
  private function generateOrigin(string $ip): Origin
  {

    $ipInfo = $this->securityCache->getItem($ip);

    if (!$ipInfo->isHit())
    {
      $ipInfo->set($this->fetchIpInfo($ip));
      $ipInfo->expiresAfter(86400);
      $this->securityCache->save($ipInfo);
    }

    $ipInfoValue = $ipInfo->get();

    $origin = new Origin();
    $origin->setIp($ipInfoValue['ip'] ?? null);
    $origin->setIpDecimal($ipInfoValue['ip_decimal'] ?? null);
    $origin->setCountry($ipInfoValue['country'] ?? null);
    $origin->setCountryIso($ipInfoValue['country_iso'] ?? null);
    $origin->setCountryEu($ipInfoValue['country_eu']);
    $origin->setRegion($ipInfoValue['region_name'] ?? null);
    $origin->setRegionCode($ipInfoValue['region_code'] ?? null);
    $origin->setCity($ipInfoValue['city'] ?? null);
    $origin->setAsn($ipInfoValue['asn'] ?? null);
    $origin->setAsn($ipInfoValue['asn_org'] ?? null);

    return $origin;

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
      CURLOPT_CUSTOMREQUEST => "GET"
    ]);

    $response = curl_exec($curl);
    $err = curl_error($curl);
    curl_close($curl);

    if ($err) {
      throw new \Exception($err);
    } else {
      return json_decode($response, true);
    }
  }
}
