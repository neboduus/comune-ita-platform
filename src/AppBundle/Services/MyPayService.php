<?php


namespace AppBundle\Services;


use AppBundle\Entity\CPSUser;
use AppBundle\Entity\Pratica;
use AppBundle\Payment\Gateway\MyPay;
use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\RouterInterface;

class MyPayService
{
  /**
   * @var Client
   */
  private $client;

  /**
   * @var RouterInterface
   */
  private $router;

  /**
   * @var LoggerInterface
   */
  private $logger;

  /**
   * MyPayService constructor.
   * @param Client $client
   * @param RouterInterface $router
   * @param LoggerInterface $logger
   */
  public function __construct(Client $client, RouterInterface $router, LoggerInterface $logger)
  {
    $this->client = $client;
    $this->router = $router;
    $this->logger = $logger;
  }

  public function getSanitizedPaymentData(Pratica $pratica): array
  {
    $data = $pratica->getPaymentData();

    if (!isset($data[MyPay::IMPORTO])) {
      $data[MyPay::IMPORTO] = $this->calculateImporto($pratica);
    }
    return $data;
  }

  /**
   * @param Pratica $pratica
   * @return array
   * @throws \Exception
   */
  public function createPaymentRequestForPratica(Pratica $pratica): array
  {
    $data = $pratica->getPaymentData();
    $requestBody = $this->createimportaDovutoRequestBody($pratica);
    $body = $this->client->post('/importaDovuto', ['body' => json_encode($requestBody)])->getBody()->getContents();

    $decoded = json_decode($body, true);

    unset($requestBody['password']);
    unset($requestBody['codIpaEnte']);

    if ($decoded['status'] === 'KO') {
      $this->logger->error('MyPay wrapper error response when creating a payment Request', ['request' => $requestBody, 'response' => $decoded]);
      throw new \Exception("Unable to create a payment request.");
    }

    $data['request'] = $requestBody;
    $data['response'] = $decoded['json'];


    $requestBody = $this->createVerificaAvvisoRequestBody($pratica, $data['response']['identificativoUnivocoVersamento']);
    $body = $this->client->post('/verificaAvviso', ['body' => json_encode($requestBody)])->getBody()->getContents();
    $decoded = json_decode($body, true);

    if ($decoded['status'] === 'KO') {
      $this->logger->error('MyPay wrapper error response when creating a payment Request', ['request' => $requestBody, 'response' => $decoded]);
      throw new \Exception("Unable to create a payment request.");
    }

    $data['response']['idSession'] = $decoded['json']['idSession'];
    $data['response']['url'] = $decoded['json']['url'];

    $pratica->setPaymentData($data);
    return $decoded;
  }

  /**
   * @param Pratica $pratica
   * @return mixed
   */
  public function getMyPayUrlForCurrentPayment(Pratica $pratica)
  {
    $data = $pratica->getPaymentData();
    return $data['response']['url'];
  }

  /**
   * @param Pratica $pratica
   * @return string
   */
  public function renderCallbackUrlForPayment(Pratica $pratica): string
  {
    $currentPraticaResumeEditUrl = $this->router->generate('pratiche_payment_callback', [
      'pratica' => $pratica->getId()
    ], RouterInterface::ABSOLUTE_URL);
    return $currentPraticaResumeEditUrl;
  }

  /**
   * @param Pratica $pratica
   * @return string
   */
  public function renderUrlForPaymentOutcome(Pratica $pratica): string
  {
    $currentPraticaResumeEditUrl = $this->router->generate('applications_payment_api_post', [
      'id' => $pratica->getId()
    ], RouterInterface::ABSOLUTE_URL);
    return $currentPraticaResumeEditUrl;
  }

  /**
   * @param Pratica $pratica
   * @return array
   */
  private function createInviaDovutiRequestBody(Pratica $pratica)
  {
    $data = $pratica->getPaymentData();
    $paymentParameters = $pratica->getServizio()->getPaymentParameters();

    $amount = $this->calculateImporto($pratica);
    if ( !$amount) {
      throw new \InvalidArgumentException('Missing amount');
    }

    $order = array(
      'identificativoUnivocoDovuto' => $this->calculateIUDFromPratica($pratica),
      'causaleVersamento' => "Pratica: " . $pratica->getId(),
      'datiSpecificiRiscossione' => $paymentParameters['gateways']['mypay']['parameters']['datiSpecificiRiscossione'],
      'importoSingoloVersamento' => $amount,
      'identificativoTipoDovuto' => $paymentParameters['gateways']['mypay']['parameters']['identificativoTipoDovuto'],
    );

    $data = array(
      'enteSILInviaRispostaPagamentoUrl' => $this->renderCallbackUrlForPayment($pratica), // Callback url
      'tipoIdentificativoUnivoco' => 'F',
      'codiceIdentificativoUnivoco' => $pratica->getRichiedenteCodiceFiscale(), // Codice fiscale
      'anagraficaPagatore' => $pratica->getRichiedenteNome() . ' ' . $pratica->getRichiedenteCognome(), // Nome e Cognome
      'indirizzoPagatore' => '',
      'civicoPagatore' => '',
      'capPagatore' => '',
      'localitaPagatore' => '',
      'provinciaPagatore' => "L'Aquila",
      'nazionePagatore' => 'IT',
      'e-mailPagatore' => $pratica->getUser()->getEmail(),
      'codIpaEnte' => $paymentParameters['gateways']['mypay']['parameters']['codIpaEnte'],
      'password' => $paymentParameters['gateways']['mypay']['parameters']['password'],
      'dovuti' => array($order)
    );

    return $data;
  }


  /**
   * @param Pratica $pratica
   * @return array
   */
  private function createimportaDovutoRequestBody(Pratica $pratica)
  {
    $data = $pratica->getPaymentData();
    $paymentParameters = $pratica->getServizio()->getPaymentParameters();
    $paymentDayLifeTime = 5;

    $amount = $this->calculateImporto($pratica);
    if ( !$amount) {
      throw new \InvalidArgumentException('Missing amount');
    }

    /** @var CPSUser $user */
    $user = $pratica->getUser();


    $data = array(
      'notifyUrl' => $this->renderUrlForPaymentOutcome($pratica),
      'enteSILInviaRispostaPagamentoUrl' => $this->renderCallbackUrlForPayment($pratica), // Callback url
      'tipoIdentificativoUnivoco' => 'F',
      'codiceIdentificativoUnivoco' => $pratica->getRichiedenteCodiceFiscale(), // Codice fiscale
      'anagraficaPagatore' => $pratica->getRichiedenteNome() . ' ' . $pratica->getRichiedenteCognome(), // Nome e Cognome
      'indirizzoPagatore' => $user->getIndirizzoResidenza(),
      'civicoPagatore' => '',
      'capPagatore' => $user->getCapResidenza(),
      'localitaPagatore' => $user->getCittaResidenza(),
      'provinciaPagatore' => $user->getProvinciaDomicilio() ? $user->getProvinciaDomicilio() : 'TN',
      'nazionePagatore' => 'IT',
      'e-mailPagatore' => $pratica->getUser()->getEmail(),
      'codIpaEnte' => $paymentParameters['gateways']['mypay']['parameters']['codIpaEnte'],
      'password' => $paymentParameters['gateways']['mypay']['parameters']['password'],
      'identificativoUnivocoDovuto' => $this->calculateIUDFromPratica($pratica),
      'causaleVersamento' => "Pratica: " . $pratica->getId(),
      'datiSpecificiRiscossione' => $paymentParameters['gateways']['mypay']['parameters']['datiSpecificiRiscossione'],
      'importoSingoloVersamento' => $amount,
      'identificativoTipoDovuto' => $paymentParameters['gateways']['mypay']['parameters']['identificativoTipoDovuto'],
      'flagGeneraIuv' => true
    );

    if ( !empty($paymentDayLifeTime) && $paymentDayLifeTime > 0 ) {
      $expireDate = time() + 60 * 60 * 24 * $paymentDayLifeTime;
      $data['dataEsecuzionePagamento']= date('Y-m-d', $expireDate );
    }

    return $data;
  }

  /**
   * @param Pratica $pratica
   * @return array
   */
  private function createVerificaAvvisoRequestBody(Pratica $pratica, $iuv)
  {
    $paymentParameters = $pratica->getServizio()->getPaymentParameters();

    $data = array(
      'enteSILInviaRispostaPagamentoUrl' => $this->renderCallbackUrlForPayment($pratica), // Callback url
      'codIpaEnte' => $paymentParameters['gateways']['mypay']['parameters']['codIpaEnte'],
      'password' => $paymentParameters['gateways']['mypay']['parameters']['password'],
      'identificativoUnivocoVersamento' => $iuv
    );

    return $data;
  }

  /**
   * @param Pratica $pratica
   * @return string
   */
  private function calculateIUDFromPratica(Pratica $pratica)
  {
    return str_replace('-', '', $pratica->getId());
  }

  /**
   * @param Pratica $pratica
   * @return int
   */
  private function calculateImporto(Pratica $pratica): int
  {

    $data = $pratica->getDematerializedForms();

    if (isset($data['flattened']['payment_amount']) && is_numeric(str_replace(',', '.', $data['flattened']['payment_amount']))) {
      return str_replace(',', '.', $data['flattened']['payment_amount']);
    }

    if (isset($pratica->getServizio()->getPaymentParameters()['total_amounts'])
        && is_numeric(str_replace(',', '.', $pratica->getServizio()->getPaymentParameters()['total_amounts']))) {
      return str_replace(',', '.', $pratica->getServizio()->getPaymentParameters()['total_amounts']);
    }

    return false;
  }

}
