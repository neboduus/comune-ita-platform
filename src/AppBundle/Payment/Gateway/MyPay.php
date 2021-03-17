<?php

namespace AppBundle\Payment\Gateway;


use AppBundle\Entity\Pratica;
use AppBundle\Form\Extension\TestiAccompagnatoriProcedura;
use AppBundle\Payment\AbstractPaymentData;
use AppBundle\Payment\PaymentDataInterface;
use AppBundle\Services\MyPayService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\VarDumper\VarDumper;

class MyPay extends AbstractPaymentData implements EventSubscriberInterface
{

  /**
   * The expected structure of paymentData is
   * importo: 1234 (integer, actual value is this / 100 )
   * payment_attempts: [
   *   payment_id_sent_to_gw: [
   *       start_request:    array (actual request sent)
   *       start_response:   array (actual remote response)
   *       outcome_request:  array (actual request sent)
   *       outcome_response: array (actual remote response)
   *     ]
   *   payment_id_sent_to_gw
   *   payment_id_sent_to_gw
   *   ...
   * ]
   * overall_outcome: -1 for pending, 0 for good, values from documentation for other cases
   */

  const PAYMENT_ATTEMPTS = 'payment_attempts';
  const IMPORTO = 'total_amounts';
  const OVERALL_OUTCOME = 'overall_outcome';

  const OUTCOME_REQUEST = 'outcome_request';
  const OUTCOME_RESPONSE = 'outcome_response';
  const START_REQUEST = 'start_request';
  const START_RESPONSE = 'start_response';

  /** Pending */
  const PAA_PAGAMENTO_NON_INIZIATO = 'PAA_PAGAMENTO_NON_INIZIATO';
  const PAA_PAGAMENTO_IN_CORSO = 'PAA_PAGAMENTO_IN_CORSO';

  /** Effettivamente conclusi negativamente */
  const PAA_PAGAMENTO_ANNULLATO = 'PAA_PAGAMENTO_ANNULLATO';
  const PAA_PAGAMENTO_SCADUTO = 'PAA_PAGAMENTO_SCADUTO';

  const ESITO_UNSET = -999;
  const ESITO_PENDING = -1;
  const ESITO_ESEGUITO = 0;
  const ESITO_NON_ESEGUITO = 1;
  const ESITO_PARZIALMENTE_ESEGUITO = 2;
  const ESITO_DECORRENZA_TERMINI = 3;
  const ESITO_PARZIALE_DECORRENZA_TERMINI = 4;
  const LATEST_ATTEMPT_ID = 'latest_attempt_id';

  const PAYMENT_STEP_LABEL = 'steps.common.payment_gateway.label';

  /**
   * @var LoggerInterface
   */
  private $logger;

  /**
   * @var MyPayService
   */
  private $myPayService;

  /**
   * @var EntityManagerInterface
   */
  private $em;

  /**
   * @var TranslatorInterface $translator
   */
  private $translator;

  public function __construct(LoggerInterface $logger, MyPayService $myPayService, EntityManagerInterface $em, RouterInterface $router, TranslatorInterface $translator)
  {
    $this->logger = $logger;
    $this->myPayService = $myPayService;
    $this->em = $em;
    $this->router = $router;
    $this->translator = $translator;
  }

  public static function getPaymentParameters()
  {
    return [
      'codIpaEnte'                => 'Codice Ipa ente',
      'password'                  => 'Password ente',
      'datiSpecificiRiscossione'  => 'Dati specifici per la riscossione',
      'identificativoTipoDovuto'  => 'Identificativo tipo dovuto'
    ];
  }

  public static function getFields()
  {
    return array(
      self::PAYMENT_ATTEMPTS,
      self::IMPORTO
    );
  }

  /** Event Subscriber **/
  public static function getSubscribedEvents()
  {
    return array(
      FormEvents::PRE_SET_DATA => 'onPreSetData',
      FormEvents::PRE_SUBMIT => 'onPreSubmit'
    );
  }

  /**
   * @param $data
   * @return mixed|void
   */
  public static function getSimplifiedData($data)
  {
    $result = [];
    $result['status'] = PaymentDataInterface::STATUS_PAYMENT_PENDING;
    if (isset($data['payment_amount'])) {
      $result['payment_amount'] = number_format( floatval($data['payment_amount']), 2, '.', '' );
    }

    if (isset($data['payment_financial_report'])) {
      $financialReport = $data['payment_financial_report'];
      foreach ($financialReport as $k => $v) {
        $financialReport[$k]['importo'] = number_format( floatval($v['importo']), 2, '.', '' );
      }
      $result['payment_financial_report'] = $financialReport;
    }

    // Request
    if (isset($data['request'])) {
      $result['status'] = PaymentDataInterface::STATUS_PAYMENT_PROCESSING;
      $paymentDate = $data['request']['dataEsecuzionePagamento'];
      try {
        $date = new \DateTime($data['request']['dataEsecuzionePagamento']);
        $paymentDate = $date->format(\DateTime::W3C);
      } catch (\Exception $e) {
        // Do nothing
      }

      $result['iud'] = $data['request']['identificativoUnivocoDovuto'];
      $result['reason'] = $data['request']['causaleVersamento'];
      $result['paid_at'] = $paymentDate;
    }

    // Response
    if (isset($data['response'])) {
      $result['iuv'] = $data['response']['identificativoUnivocoVersamento'];
    }

    // Result
    if (isset($data['outcome'])) {
      if ($data['outcome']['status'] == 'OK') {
        $result['status'] = PaymentDataInterface::STATUS_PAYMENT_PAID;
      } else {
        $result['status'] = PaymentDataInterface::STATUS_PAYMENT_FAILED;
      }
      $result['mypay_status_code'] = $data['outcome']['status_code'];
      $result['mypay_status_message'] = $data['outcome']['status_message'];
    }
    return $result;
  }

  public function onPreSetData(FormEvent $event)
  {
    $form = $event->getForm();
    /** @var Pratica $pratica */
    $pratica = $event->getData();
    $options = $event->getForm()->getConfig()->getOptions();
    /** @var TestiAccompagnatoriProcedura $helper */
    $helper = $options["helper"];
    $data = $pratica->getPaymentData();


    try {
      if (isset($data['response']) && $data['response']['esito'] == 'OK') {

        $url = $this->myPayService->getMyPayUrlForCurrentPayment($pratica);
        $helper->setDescriptionText($this->generatePaymentButtons($pratica, $url));

      } else {

        $this->myPayService->createPaymentRequestForPratica($pratica);
        $this->em->flush();
        $url = $this->myPayService->getMyPayUrlForCurrentPayment($pratica);
        $helper->setDescriptionText($this->generatePaymentButtons($pratica, $url));

      }

    } catch (\Exception $e) {
      $this->logger->error("Warning user about not being able to create a payment request for pratica " . $pratica->getId() . ' - ' . $e->getMessage());
      $helper->setDescriptionText("C'è stato un errore nella creazione della richiesta di pagamento, contatta l'assistenza.");
    }
  }

  /**
   * @param FormEvent $event
   */
  public function onPreSubmit(FormEvent $event)
  {
    /** @var Pratica $application */
    $application = $event->getForm()->getData();
    $data = $application->getPaymentData();

    if (!isset($data['response']) || empty($data['response'])) {
      $event->getForm()->addError(
        new FormError('Devi scegliere almeno un metodo di pagamento')
      );
    }
  }

  /**
   * @param Pratica $pratica
   * @param $url
   * @return string
   */
  private function generatePaymentButtons( Pratica $pratica, $url )
  {

    $urlAvviso = htmlspecialchars_decode($url['urlFileAvviso']);

    $buttons = '<div class="row mt-5"><div class="col-sm-4"><strong>'.$this->translator->trans('pratica.numero').'</strong></div><div class="col-sm-8 d-inline-flex"><code>'.$pratica->getId().'</code></div></div>';
    $buttons .= "<p class='mt-5'>".$this->translator->trans('gateway.mypay.redirect_text')."</p><div class='text-center mt-5'><a href='{$url['url']}' class='btn btn-lg btn-primary'>".$this->translator->trans('gateway.mypay.redirect_button')."</a></div>";
    $buttons .= "<p class='mt-5'>".$this->translator->trans('gateway.mypay.download_text')."</p><div class='text-center mt-5'><a href='{$urlAvviso}' class='btn btn-lg btn-secondary'>".$this->translator->trans('gateway.mypay.download_button')."</a></div>";
    return $buttons;
  }
}
