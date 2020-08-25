<?php

namespace AppBundle\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class OpenLoginAuthenticator extends AbstractAuthenticator
{
  public function __construct(UrlGeneratorInterface $urlGenerator)
  {
    $this->urlGenerator = $urlGenerator;
  }

  public function supports(Request $request)
  {
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

}
