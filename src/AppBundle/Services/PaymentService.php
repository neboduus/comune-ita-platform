<?php

namespace AppBundle\Services;

use AppBundle\Entity\CPSUser;
use AppBundle\Entity\PaymentGateway;
use AppBundle\Entity\Pratica;
use AppBundle\Form\Admin\Servizio\PaymentDataType;
use DateTimeInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Translation\TranslatorInterface;

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

  /**
   * @param RouterInterface $router
   * @param LoggerInterface $logger
   */
  public function __construct(RouterInterface $router, LoggerInterface $logger)
  {
    $this->router = $router;
    $this->logger = $logger;
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

    if (!isset($paymentData['split'])) {
      $paymentData['split'] = [];
    }

    $paymentDayLifeTime = 90;
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

    $paymentData['expire_at'] = (new \DateTime())->modify('+'.$paymentDayLifeTime.'days')->format(DateTimeInterface::W3C);
    $paymentData['notify'] = [
      'url' => $this->generateNotifyUrl($pratica),
      'method' => 'POST'
    ];
    $paymentData['landing'] = $this->generateCallbackUrl($pratica);

    /*if (isset($data[PaymentDataType::PAYMENT_FINANCIAL_REPORT])) {
      foreach ( $data[PaymentDataType::PAYMENT_FINANCIAL_REPORT] as $v ) {
        $temp =[];
        $temp['codCapitolo'] = $v['codCapitolo'];
        $temp['codUfficio'] = $v['codUfficio'];
        $temp['accertamento']['importo'] = $v['importo'];
        if (isset($v['codAccertamento']) && !empty($v['codAccertamento'])) {
          $temp['accertamento']['codAccertamento'] = $v['codAccertamento'];
        }
        $request['payment_split'][]= $temp;
      }
    }*/
    return $paymentData;
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
  private function getCodiceFiscale(Pratica $pratica): string
  {
    $cf = $pratica->getUser()->getCodiceFiscale();
    $cfParts = explode('-', $cf);
    if ( count($cfParts) > 1) {
      $cf = $cfParts[0];
    }
    return $cf;
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
