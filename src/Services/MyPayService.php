<?php


namespace App\Services;


use App\Entity\CPSUser;
use App\Entity\Pratica;
use App\Payment\Gateway\MyPay;
use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\RouterInterface;
use App\Form\Admin\Servizio\PaymentDataType;

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

  /**
   * @param Pratica $pratica
   * @return bool|array
   */
  public function getSanitizedPaymentData(Pratica $pratica)
  {
    $data = $pratica->getDematerializedForms();

    if (isset($data['flattened'][PaymentDataType::PAYMENT_AMOUNT]) && is_numeric(str_replace(',', '.', $data['flattened'][PaymentDataType::PAYMENT_AMOUNT]))) {
      $paymentData[PaymentDataType::PAYMENT_AMOUNT] = str_replace(',', '.', $data['flattened'][PaymentDataType::PAYMENT_AMOUNT]);

      if (isset($data['flattened'][PaymentDataType::PAYMENT_FINANCIAL_REPORT])) {
        $paymentData[PaymentDataType::PAYMENT_FINANCIAL_REPORT] = $data['flattened'][PaymentDataType::PAYMENT_FINANCIAL_REPORT];
      }
      if (isset($data['flattened'][PaymentDataType::PAYMENT_DESCRIPTION]) && !empty($data['flattened'][PaymentDataType::PAYMENT_DESCRIPTION])) {
        $paymentData[PaymentDataType::PAYMENT_DESCRIPTION] = $data['flattened'][PaymentDataType::PAYMENT_DESCRIPTION];
      } else {
        $paymentData[PaymentDataType::PAYMENT_DESCRIPTION] = $pratica->getId() . ' - ' . $pratica->getUser()->getCodiceFiscale();
      }
      return $paymentData;
    }

    // Fallback su configurazione dal backend
    if (isset($pratica->getServizio()->getPaymentParameters()['total_amounts'])
      && is_numeric(str_replace(',', '.', $pratica->getServizio()->getPaymentParameters()['total_amounts']))) {
      $paymentData[PaymentDataType::PAYMENT_AMOUNT] = str_replace(',', '.', $pratica->getServizio()->getPaymentParameters()['total_amounts']);
      return $paymentData;
    }

    return false;
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
    return $data['response'];
  }

  /**
   * @param Pratica $pratica
   * @return string
   */
  public function renderCallbackUrlForPayment(Pratica $pratica, $anonymous = false): string
  {
    return $this->router->generate('pratiche_payment_callback', [
      'pratica' => $pratica->getId()
    ], RouterInterface::ABSOLUTE_URL);
  }

  /**
   * @param Pratica $pratica
   * @return string
   */
  public function renderUrlForPaymentOutcome(Pratica $pratica): string
  {
    return $this->router->generate('applications_payment_api_post', [
      'id' => $pratica->getId()
    ], RouterInterface::ABSOLUTE_URL);
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

    $amount = $data[PaymentDataType::PAYMENT_AMOUNT];
    if (!$amount) {
      throw new \InvalidArgumentException('Missing amount');
    }

    $description = $data[PaymentDataType::PAYMENT_DESCRIPTION];

    /** @var CPSUser $user */
    $user = $pratica->getUser();

    $provincia = $user->getProvinciaResidenza() ? $user->getProvinciaResidenza() : 'TN';
    if (strlen($provincia) > 2) {
      $provincia = substr($provincia, 0, 2);
    }

    $cf = $pratica->getUser()->getCodiceFiscale();
    $callBackUrl = $this->renderCallbackUrlForPayment($pratica);
    $cfParts = explode('-', $cf);
    if ( $this->isPayerAnonymous( $cf ) ) {
      $cf = $cfParts[0];
      $callBackUrl = $this->renderCallbackUrlForPayment($pratica, true);
    }

    $request = array(
      'notifyUrl' => $this->renderUrlForPaymentOutcome($pratica),
      'enteSILInviaRispostaPagamentoUrl' => $callBackUrl, // Callback url
      'tipoIdentificativoUnivoco' => 'F',
      'codiceIdentificativoUnivoco' => $cf, // Codice fiscale
      'anagraficaPagatore' => $pratica->getUser()->getNome() . ' ' . $pratica->getUser()->getCognome(), // Nome e Cognome
      'indirizzoPagatore' => $user->getIndirizzoResidenza(),
      'civicoPagatore' => '',
      'capPagatore' => $user->getCapResidenza(),
      'localitaPagatore' => $user->getCittaResidenza(),
      'provinciaPagatore' => $provincia,
      'nazionePagatore' => 'IT',
      'e-mailPagatore' => $pratica->getUser()->getEmail(),
      'codIpaEnte' => $paymentParameters['gateways']['mypay']['parameters']['codIpaEnte'],
      'password' => $paymentParameters['gateways']['mypay']['parameters']['password'],
      'identificativoUnivocoDovuto' => $this->calculateIUDFromPratica($pratica),
      'causaleVersamento' => $description,
      'datiSpecificiRiscossione' => $paymentParameters['gateways']['mypay']['parameters']['datiSpecificiRiscossione'],
      'importoSingoloVersamento' => $amount,
      'identificativoTipoDovuto' => $paymentParameters['gateways']['mypay']['parameters']['identificativoTipoDovuto'],
      'flagGeneraIuv' => true
    );

    if (isset($data[PaymentDataType::PAYMENT_FINANCIAL_REPORT])) {
      foreach ( $data[PaymentDataType::PAYMENT_FINANCIAL_REPORT] as $v ) {
        $temp =[];
        $temp['codCapitolo'] = $v['codCapitolo'];
        $temp['codUfficio'] = $v['codUfficio'];
        $temp['accertamento']['importo'] = $v['importo'];
        if (isset($v['codAccertamento']) && !empty($v['codAccertamento'])) {
          $temp['accertamento']['codAccertamento'] = $v['codAccertamento'];
        }
        $request['bilancio'][]= $temp;
      }
    }

    if (!empty($paymentDayLifeTime) && $paymentDayLifeTime > 0) {
      $expireDate = time() + 60 * 60 * 24 * $paymentDayLifeTime;
      $request['dataEsecuzionePagamento'] = date('Y-m-d', $expireDate);
    }

    return $request;
  }

  /**
   * @param Pratica $pratica
   * @return array
   */
  private function createVerificaAvvisoRequestBody(Pratica $pratica, $iuv)
  {
    $paymentParameters = $pratica->getServizio()->getPaymentParameters();

    $cf = $pratica->getUser()->getCodiceFiscale();
    $callBackUrl = $this->renderCallbackUrlForPayment($pratica);
    if ( $this->isPayerAnonymous( $cf ) ) {
      $callBackUrl = $this->renderCallbackUrlForPayment($pratica, true);
    }

    $data = array(
      'enteSILInviaRispostaPagamentoUrl' => $callBackUrl, // Callback url
      'codIpaEnte' => $paymentParameters['gateways']['mypay']['parameters']['codIpaEnte'],
      'password' => $paymentParameters['gateways']['mypay']['parameters']['password'],
      'identificativoUnivocoVersamento' => $iuv
    );

    return $data;
  }

  /**
   * @param $cf
   * @return bool
   */
  private function isPayerAnonymous($cf)
  {
    $cfParts = explode('-', $cf);
    if ( count($cfParts) > 1) {
      return true;
    }
    return false;
  }

  /**
   * @param Pratica $pratica
   * @return string
   */
  private function calculateIUDFromPratica(Pratica $pratica)
  {
    return str_replace('-', '', $pratica->getId());
  }

}
