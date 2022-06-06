<?php

namespace AppBundle\Services;

use AppBundle\Entity\Pratica;
use AppBundle\Form\Admin\Servizio\PaymentDataType;
use DateTimeInterface;

use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\RouterInterface;


class PaymentService
{

  /**
   * @var RouterInterface
   */
  private $router;

  /**
   * @var LoggerInterface
   */
  private $logger;

  private $ksqlDBUrl;

  /**
   * @param RouterInterface $router
   * @param LoggerInterface $logger
   * @param $ksqlDBUrl
   */
  public function __construct(RouterInterface $router, LoggerInterface $logger, $ksqlDBUrl)
  {
    $this->router = $router;
    $this->logger = $logger;
    $this->ksqlDBUrl = $ksqlDBUrl;
  }

  /**
   * @param Pratica $pratica
   * @return array
   * @throws \Exception
   */
  public function createPaymentData(Pratica $pratica)
  {
    $paymentData = [];
    $data = $pratica->getDematerializedForms();

    // Amount
    if (isset($data['flattened'][PaymentDataType::PAYMENT_AMOUNT]) && is_numeric(str_replace(',', '.', $data['flattened'][PaymentDataType::PAYMENT_AMOUNT]))) {
      $paymentData['amount'] = str_replace(',', '.', $data['flattened'][PaymentDataType::PAYMENT_AMOUNT]);

      if (isset($data['flattened'][PaymentDataType::PAYMENT_FINANCIAL_REPORT])) {
        $paymentData['split'] = $data['flattened'][PaymentDataType::PAYMENT_FINANCIAL_REPORT];
      }
    } elseif (isset($pratica->getServizio()->getPaymentParameters()['total_amounts'])
      && is_numeric(str_replace(',', '.', $pratica->getServizio()->getPaymentParameters()['total_amounts']))) {
      $paymentData['amount'] = str_replace(',', '.', $pratica->getServizio()->getPaymentParameters()['total_amounts']);
    }

    // Reason
    if (isset($data['flattened'][PaymentDataType::PAYMENT_DESCRIPTION]) && !empty($data['flattened'][PaymentDataType::PAYMENT_DESCRIPTION])) {
      $paymentData['reason'] = $data['flattened'][PaymentDataType::PAYMENT_DESCRIPTION];
    } else {
      $paymentData['reason'] = $pratica->getId() . ' - ' . $pratica->getUser()->getCodiceFiscale();
    }

    if (isset($data[PaymentDataType::PAYMENT_FINANCIAL_REPORT])) {
      foreach ( $data[PaymentDataType::PAYMENT_FINANCIAL_REPORT] as $v ) {
        $temp =[];
        $temp['code'] = $v['codCapitolo'];
        $temp['amount'] = $v['importo'];
        $temp['meta']['codUfficio'] = $v['codUfficio'];

        if (isset($v['codAccertamento']) && !empty($v['codAccertamento'])) {
          $temp['meta']['codAccertamento'] = $v['codAccertamento'];
        }
        $paymentData['split'][]= $temp;
      }
    }

    if (!isset($paymentData['split'])) {
      $paymentData['split'] = [];
    }

    $paymentDayLifeTime = 90;
    $paymentData['expire_at'] = (new \DateTime())->modify('+'.$paymentDayLifeTime.'days')->format(DateTimeInterface::W3C);
    $paymentData['notify'] = [
      'url' => $this->generateNotifyUrl($pratica),
      'method' => 'POST'
    ];

    $paymentData['landing'] = [
      'url' => $this->generateCallbackUrl($pratica),
      'method' => 'GET'
    ];

    return $paymentData;
  }

  /**
   * @param Pratica $application
   * @return array
   * @throws \Exception
   */
  public function getPaymentStatusByApplication(Pratica $application): array
  {
    $data = [];
    $curl = curl_init();
    $payload = [
      'ksql' => "SELECT id, reason, status, created_at, updated_at, online_payment_begin_url, online_payment_begin_method, online_payment_landing_url, online_payment_landing_method, offline_payment_url, offline_payment_method FROM payments_detail WHERE remote_id = '{$application->getId()}';",
    ];

    $url = $this->ksqlDBUrl . '/query';
    curl_setopt_array($curl, [
      CURLOPT_URL => $url,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 30,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "POST",
      CURLOPT_POSTFIELDS => json_encode($payload),
      CURLOPT_HTTPHEADER => [
        "Accept: application/vnd.ksql.v1+json",
        "Content-Type: application/json"
      ],
    ]);

    $response = curl_exec($curl);
    $err = curl_error($curl);
    curl_close($curl);

    if ($err) {
      throw new \Exception($err);
    } else {
      $responseData = json_decode($response, true);

      if (!isset($responseData[1]) || empty($responseData[1])) {
        return $data;
      }

      $data = [
        "id" => $responseData[1]['row']['columns'][0],
        "reason" => $responseData[1]['row']['columns'][1],
        "status" => $responseData[1]['row']['columns'][2],
        "created_at" => $responseData[1]['row']['columns'][3],
        "updated_at" => $responseData[1]['row']['columns'][4],
      ];

      if (strtoupper($data['status']) === 'PAYMENT_PENDING') {
        $data['links'] = [
          'online_payment_begin' => [
            'url' => $responseData[1]['row']['columns'][5],
            'method' => $responseData[1]['row']['columns'][6],
          ],
          'online_payment_landing' => [
            'url' => $responseData[1]['row']['columns'][7],
            'method' => $responseData[1]['row']['columns'][8],
          ],
          'offline_payment' => [
            'url' => $responseData[1]['row']['columns'][9],
            'method' => $responseData[1]['row']['columns'][10],
          ],
        ];
      }
      return $data;
    }
  }

  /**
   * @param $remoteId
   * @return array
   * @throws \Exception
   */
  public function getPayments($remoteId): array
  {
    $data = [];
    $curl = curl_init();

    $query = "SELECT ID, USER_ID, TYPE, TENANT_ID, SERVICE_ID, CREATED_AT, UPDATED_AT, STATUS, REASON, REMOTE_ID, PAYMENT_TRANSACTION_ID, PAYMENT_PAID_AT, PAYMENT_EXPIRE_AT, PAYMENT_AMOUNT, PAYMENT_CURRENCY, PAYMENT_NOTICE_CODE, PAYMENT_IUD, PAYMENT_IUV, ONLINE_PAYMENT_BEGIN_URL, ONLINE_PAYMENT_BEGIN_LAST_OPENED_AT, ONLINE_PAYMENT_BEGIN_METHOD, ONLINE_PAYMENT_LANDING_URL, ONLINE_PAYMENT_LANDING_LAST_OPENED_AT, ONLINE_PAYMENT_LANDING_METHOD, OFFLINE_PAYMENT_URL, OFFLINE_PAYMENT_LAST_OPENED_AT, OFFLINE_PAYMENT_METHOD, RECEIPT_URL, RECEIPT_LAST_OPENED_AT, RECEIPT_METHOD, UPDATE_URL, UPDATE_LAST_CHECK_AT, UPDATE_NEXT_CHECK_AT, UPDATE_METHOD, PAYER_TYPE, PAYER_TAX_IDENTIFICATION_NUMBER, PAYER_NAME, PAYER_FAMILY_NAME, PAYER_STREET_NAME, BUILDING_NUMBER, POSTAL_CODE, TOWN_NAME, COUNTRY_SUBDIVISION, COUNTRY, EMAIL, EVENT_ID, EVENT_VERSION, EVENT_CREATED_AT, APP_ID FROM payments_detail";
    if ($remoteId) {
      $query .=  " WHERE remote_id = '{$remoteId}'";
    }

    $payload = [
      'ksql' => $query . ';',
    ];

    $url = $this->ksqlDBUrl . '/query';
    curl_setopt_array($curl, [
      CURLOPT_URL => $url,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 30,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "POST",
      CURLOPT_POSTFIELDS => json_encode($payload),
      CURLOPT_HTTPHEADER => [
        "Accept: application/vnd.ksql.v1+json",
        "Content-Type: application/json"
      ],
    ]);

    $response = curl_exec($curl);
    $err = curl_error($curl);
    curl_close($curl);

    if ($err) {
      throw new \Exception($err);
    } else {
      $responseData = json_decode($response, true);

      if (!isset($responseData[1]) || empty($responseData[1])) {
        return $data;
      }

      // Elimino la riga con l'header
      unset($responseData[0]);

      foreach ($responseData as $v) {
        $payment = [
          'id' => $v['row']['columns'][0],
          'user_id' => $v['row']['columns'][1],
          'type' => $v['row']['columns'][2],
          'tenant_id' => $v['row']['columns'][3],
          'service_id' => $v['row']['columns'][4],
          'created_at' => $v['row']['columns'][5],
          'updated_at' => $v['row']['columns'][6],
          'status' => $v['row']['columns'][7],
          'reason' => $v['row']['columns'][8],
          'remote_id' => $v['row']['columns'][9],
          'payment' => [
            'transaction_id' => $v['row']['columns'][10],
            'paid_at' => $v['row']['columns'][11],
            'expire_at' => $v['row']['columns'][12],
            'amount' => $v['row']['columns'][13],
            'currency' => $v['row']['columns'][14],
            'notice_code' => $v['row']['columns'][15],
            'iud' => $v['row']['columns'][16],
            'iuv' => $v['row']['columns'][17],
            'split' => [],
          ],
          'links' => [
              'online_payment_begin' => [
                'url' => $v['row']['columns'][18],
                'last_opened_at' => $v['row']['columns'][19],
                'method' => $v['row']['columns'][20],
              ],
              'online_payment_landing' => [
                'url' => $v['row']['columns'][21],
                'last_opened_at' => $v['row']['columns'][22],
                'method' => $v['row']['columns'][23],
              ],
              'offline_payment' => [
                'url' => $v['row']['columns'][24],
                'last_opened_at' => $v['row']['columns'][25],
                'method' => $v['row']['columns'][26],
              ],
              'receipt' => [
                'url' => $v['row']['columns'][27],
                'last_opened_at' => $v['row']['columns'][28],
                'method' => $v['row']['columns'][29],
              ],
              'notify' => [],
              'update' => [
                'url' => $v['row']['columns'][30],
                'method' => $v['row']['columns'][31],
                'last_check_at' => $v['row']['columns'][32],
                'next_check_at' => $v['row']['columns'][33],
              ],
            ],
          'payer' => [
            'type' => $v['row']['columns'][34],
            'tax_identification_number' => $v['row']['columns'][35],
            'name' => $v['row']['columns'][36],
            'family_name' => $v['row']['columns'][37],
            'street_name' => $v['row']['columns'][38],
            'building_number' => $v['row']['columns'][39],
            'postal_code' => $v['row']['columns'][40],
            'town_name' => $v['row']['columns'][41],
            'country_subdivision' => $v['row']['columns'][42],
            'country' => $v['row']['columns'][43],
            'email' => $v['row']['columns'][44],
          ],
          'event_id' => $v['row']['columns'][45],
          'event_version' => $v['row']['columns'][46],
          'event_created_at' => $v['row']['columns'][47],
          'app_id' => $v['row']['columns'][48],
        ];
        $data []= $payment;
      }

      return $data;
    }
  }

  /**
   * @param Pratica $pratica
   * @return string
   */
  private function generateNotifyUrl(Pratica $pratica): string
  {
    return $this->router->generate('applications_payment_api_post', [
      'id' => $pratica->getId()
    ], RouterInterface::ABSOLUTE_URL);
  }

  /**
   * @param Pratica $pratica
   * @return string
   */
  private function generateCallbackUrl(Pratica $pratica): string
  {
    $cf = $pratica->getUser()->getCodiceFiscale();
    if ($this->isPayerAnonymous($cf)) {
      return $this->router->generate('pratiche_anonime_show', [
        'pratica' => $pratica->getId(),
        'hash' => $pratica->getHash()
      ], RouterInterface::ABSOLUTE_URL);
    } else {
      return $this->router->generate('pratica_show_detail', [
        'pratica' => $pratica->getId()
      ], RouterInterface::ABSOLUTE_URL);
    }
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


}
