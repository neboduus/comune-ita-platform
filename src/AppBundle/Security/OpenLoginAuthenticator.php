<?php

namespace AppBundle\Security;

use AppBundle\Dto\UserAuthenticationData;
use AppBundle\Entity\CPSUser;
use AppBundle\Services\InstanceService;
use AppBundle\Services\UserSessionService;
use Artprima\PrometheusMetricsBundle\Metrics\MetricsGeneratorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class OpenLoginAuthenticator extends AbstractAuthenticator
{
  /**
   * @var InstanceService
   */
  private $instanceService;
  /**
   * @var MetricsGeneratorInterface
   */
  private $userMetrics;

  /**
   * OpenLoginAuthenticator constructor.
   * @param UrlGeneratorInterface $urlGenerator
   * @param $loginRoute
   * @param UserSessionService $userSessionService
   * @param InstanceService $instanceService
   * @param MetricsGeneratorInterface $userMetrics
   */
  public function __construct(UrlGeneratorInterface $urlGenerator, $loginRoute, UserSessionService $userSessionService, InstanceService $instanceService, MetricsGeneratorInterface $userMetrics)
  {
    $this->urlGenerator = $urlGenerator;
    $this->loginRoute = $loginRoute;
    $this->userSessionService = $userSessionService;
    $this->instanceService = $instanceService;
    $this->userMetrics = $userMetrics;
  }

  protected function getLoginRouteSupported()
  {
    return ['login_open'];
  }

  public function supports(Request $request)
  {
    try {
      $this->checkLoginRoute();
    } catch (\Exception $e) {
      return false;
    }
    return $request->attributes->get('_route') === 'login_open' && $this->checkHeaderUserData($request);
  }

  /**
   * @param Request $request
   * @return bool
   *
   * Check if minimum header parameter is present
   */
  private function checkHeaderUserData(Request $request)
  {
    $this->hydrateHeaderUserDataIfNeeded($request);

    $fields = [
      self::KEY_PARAMETER_NAME,
      'cognome',
      'nome',
      'emailAddress',
    ];

    foreach ($fields as $field) {
      if (!$this->getHeaderValue($request, $field)) {
        return false;
      }
    }

    return true;
  }

  private function hydrateHeaderUserDataIfNeeded(Request $request)
  {
    $user = $request->headers->get('x-forwarded-user');
    $userDecoded = base64_decode($user);
    if (base64_encode($userDecoded) === $user){
      $data = (array)json_decode($userDecoded, true);
      foreach ($data as $key => $value){
        $request->headers->set('x-forwarded-user-'. $key, $value);
      }
    }
  }

  private function getHeaderValue(Request $request, $field)
  {
    $mappedField = $this->getHeadersMap()[$field];
    if (is_callable($mappedField)) {
      return call_user_func($mappedField, $request);
    } elseif (is_string($mappedField) && $request->headers->has($mappedField) && $request->headers->get($mappedField) !== '') {
      return $request->headers->get($mappedField);
    }

    return false;
  }

  /**
   * @see https://docs.italia.it/italia/spid/spid-regole-tecniche/it/stabile/attributi.html
   * @return string[]
   */
  private function getHeadersMap()
  {
    return [
      'spidCode' => 'x-forwarded-user-spidcode',

      'nome' => 'x-forwarded-user-name',

      'cognome' => 'x-forwarded-user-familyname',

      'luogoNascita' => 'x-forwarded-user-placeofbirth',

      'provinciaNascita' => 'x-forwarded-user-countyofbirth',

      'dataNascita' => function (Request $request) {
        $xsDate = $request->headers->get('x-forwarded-user-dateofbirth');
        if (!empty($xsDate)){
          $dateTime = \DateTime::createFromFormat('Y-m-d', $xsDate);
          if ($dateTime instanceof \DateTime) {
            return $dateTime->format('d/m/Y');
          }
        }
        return false;
      },

      'sesso' => 'x-forwarded-user-gender',

      //companyName

      'indirizzoResidenza' => 'x-forwarded-user-registeredoffice',

      self::KEY_PARAMETER_NAME => function (Request $request) {
        return str_replace('TINIT-', '', $request->headers->get('x-forwarded-user-fiscalnumber'));
      },

      //ivaCode

      'idCard' => 'x-forwarded-user-idcard',

      'cellulare' => 'x-forwarded-user-mobilephone',

      'emailAddress' => function (Request $request) {
        if ($request->headers->has('x-forwarded-user-digitaladdress') && !empty(
          $request->headers->get(
            'x-forwarded-user-digitaladdress'
          )
          )) {
          return $request->headers->get('x-forwarded-user-digitaladdress');
        }

        return $request->headers->get('x-forwarded-user-email');
      },

      'emailAddressPersonale' => 'x-forwarded-user-email',

      'indirizzoDomicilio' => 'x-forwarded-user-address',

      'provider' => 'x-forwarded-user-provider',
    ];
  }

  /**
   * @param Request $request
   * @param $userDataKeys
   * @return array
   */
  protected function createUserDataFromRequest(Request $request)
  {
    $data = [];
    foreach (array_keys($this->getHeadersMap()) as $field) {
      $value = $this->getHeaderValue($request, $field);
      if ($value) {
        $data[$field] = $value;
      }
    }

    // Fallback on session
    if ($data[self::KEY_PARAMETER_NAME] == null) {
      $data = $request->getSession()->get('user_data');
    }

    return $data;
  }

  protected function getRequestDataToStoreInUserSession(Request $request)
  {
    return $request->headers->all();
  }

  protected function getUserAuthenticationData(Request $request, UserInterface $user)
  {

    $dateTimeObject = new \DateTime();
    $data = [
      'authenticationMethod' => CPSUser::IDP_SPID,
      'sessionId' => $request->headers->get('x-forwarded-user-session'),
      'spidCode' => $request->headers->get('x-forwarded-user-spidcode'),
      'spidLevel' => $request->headers->get('x-forwarded-user-spid-level'),
      'instant' => $dateTimeObject->format(DATE_ISO8601),
      'sessionIndex' => $request->headers->get('x-forwarded-user-session'),
    ];

    $this->userMetrics->incLoginSuccess($this->instanceService->getCurrentInstance()->getSlug(), 'login-open', $data['authenticationMethod'], $data['spidLevel']);
    return UserAuthenticationData::fromArray($data);
  }
}
