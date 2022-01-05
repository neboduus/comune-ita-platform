<?php

namespace AppBundle\Security\DedaLogin;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;

class DedaLoginClient
{
  const TEST_BASE_URL = 'https://authtest.soluzionipa.it/spid';

  const PROD_BASE_URL = 'https://auth.soluzionipa.it/spid';

  /**
   * @var HttpClientInterface
   */
  private $httpClient;

  private $clientId;

  private $clientSecret;

  private $baseUrl;

  private $authBearer;

  public function __construct(HttpClientInterface $httpClient, $dedaLoginClientId, $dedaLoginSecret, $dedaEnv)
  {
    $this->baseUrl = strtolower($dedaEnv) === 'dev' ? self::TEST_BASE_URL : self::PROD_BASE_URL;
    $this->httpClient = $httpClient;
    $this->clientId = $dedaLoginClientId;
    $this->clientSecret = $dedaLoginSecret;
  }

  public function getMetadata(): string
  {
    $response = $this->httpClient->request(
      'GET',
      $this->baseUrl . '/get_metadata?client_id=' . $this->clientId,
      [
        'auth_bearer' => $this->getAuthBearer(),
      ]
    );

    $content = json_decode($response->getContent(), true);
    if ($content['esito'] === 'ko') {
      throw new \Exception($content['msg_errore']);
    }

    return $content['metadata'];
  }

  public function getAuthRequest(string $idp): string
  {
    $response = $this->httpClient->request(
      'GET',
      $this->baseUrl . '/get_auth_request?client_id=' . $this->clientId . '&idp=' . $idp,
      [
        'auth_bearer' => $this->getAuthBearer(),
      ]
    );

    $content = json_decode($response->getContent(), true);
    if ($content['esito'] === 'ko') {
      throw new \Exception($content['msg_errore']);
    }

    return $content['sso_request'];
  }

  public function checkAssertion($assertion): array
  {
    if ($this->baseUrl === self::TEST_BASE_URL) {
      $assertionSample = '{
          "esito": "ok",
          "provider_id": "infocert",
          "attributi_utente": {
              "spidCode": "INF___",
              "name": "Test",
              "familyName": "Example",
              "fiscalNumber": "TINIT-XMPTST77T05G224H",
              "email": "test@example.com",
              "gender": "M",
              "dateOfBirth": "1977-12-05",
              "placeOfBirth": "G224",
              "countyOfBirth": "VE",
              "idCard": "cartaIdentita AS___ Comune 2012-07-02 2022-11-09",
              "address": "Via Test 22 35045 Padova PD",
              "digitalAddress": null,
              "expirationDate": "2050-02-20",
              "mobilePhone": "+393312345789",
              "ivaCode": null,
              "registeredOffice": null
          },
          "response_id": "_ce91d0c0-f769-0138-d91c-005056a556a5",
          "info_tracciatura": {
              "response": "eJztWulu4sq2fhXEnFoATb2GBH....+jegQZbQ",
              "response_id": "_7ea5b0264e041ff82a78ff1f11afe272",
              "response_issue_instant": "2020-10-23T14:28:07.138Z",
              "response_issuer": "https:\/\/identity.infocert.it",
              "assertion_id": "_e8b5f42d67e8104aba6ae53ba018daf4",
              "assertion_subject": "_8817a7e9e0c1a755f5ae9f3f4e386410",
              "assertion_subject_name_qualifier": "https:\/\/identity.infocert.it"
          }
      }';

      return json_decode($assertionSample, true);
    }

    $response = $this->httpClient->request(
      'POST',
      $this->baseUrl . '/check_assertion',
      [
        'json' => [
          'client_id' => $this->clientId,
          'assertion' => $assertion,
        ],
        'auth_bearer' => $this->getAuthBearer(),
      ]
    );

    $content = json_decode($response->getContent(), true);
    if ($content['esito'] === 'ko') {
      throw new \Exception($content['msg_errore'] . ' ' . $content['dettaglio_log_errore']);
    }

    return $content;
  }

  public function createUserDataFromAssertion($assertion): string
  {
    $data = [
      'session' => $assertion['response_id'],
      'spid-level' => '',
      'idp-entity-id' => $assertion['provider_id'],
      'provider' => $assertion['provider_id'],
      'user-session' => $assertion['info_tracciatura']['response_id'],
    ];
    $data = array_merge($data, (array)$assertion['attributi_utente']);

    return base64_encode(json_encode($data));
  }

  private function getAuthBearer(): string
  {
    if ($this->authBearer === null) {
      $configuration = Configuration::forSymmetricSigner(
        new Sha256(),
        InMemory::plainText($this->clientSecret)
      );
      $now = new \DateTimeImmutable();
      $this->authBearer = $configuration->builder()
        ->issuedBy('Stanza del cittadino')
        ->issuedAt($now)
        ->withClaim('hash_assertion_consumer', '')
        ->withClaim('start', $now->format('dmYHis'))
        ->getToken($configuration->signer(), $configuration->signingKey())
        ->toString();
    }

    return $this->authBearer;
  }
}
