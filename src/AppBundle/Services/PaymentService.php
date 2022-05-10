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

    /*
      {
        "reason": "",
        "amount": "",
        "expire_at": "",
        "split": [
            {"code": "", "amount": "", "meta": {}},
            {"code": "", "amount": "", "meta": {}}
        ]
        "notify": { "url": "https://www2.stanzadelcittadino.it/{tenant_name}/api/applications/{application_id}/payment", "method": "POST" }
        "landing": "url"
      }
    */

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
  public function getPymentStatusByApplication(Pratica $application): array
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
      return $this->router->generate('pratiche_anonime_payment_callback', [
        'pratica' => $pratica->getId(),
        'hash' => $pratica->getHash()
      ], RouterInterface::ABSOLUTE_URL);
    } else {
      return $this->router->generate('pratiche_payment_callback', [
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
