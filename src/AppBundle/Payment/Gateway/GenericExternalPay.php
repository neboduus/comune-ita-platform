<?php

namespace AppBundle\Payment\Gateway;


use AppBundle\Entity\CPSUser;
use AppBundle\Entity\PaymentGateway;
use AppBundle\Entity\Pratica;
use AppBundle\Form\Admin\Servizio\PaymentDataType;
use AppBundle\Form\Extension\TestiAccompagnatoriProcedura;
use AppBundle\Payment\AbstractPaymentData;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Translation\TranslatorInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;


class GenericExternalPay extends AbstractPaymentData implements EventSubscriberInterface
{

  const IMPORTO = 'total_amounts';

  /**
   * @var LoggerInterface
   */
  private $logger;

  /**
   * @var EntityManagerInterface
   */
  private $entityManager;

  /**
   * @var TranslatorInterface $translator
   */
  private $translator;

  /**
   * @var RouterInterface
   */
  private $router;

  /**
   * @param LoggerInterface $logger
   * @param EntityManagerInterface $entityManager
   * @param RouterInterface $router
   * @param TranslatorInterface $translator
   */
  public function __construct(LoggerInterface $logger, EntityManagerInterface $entityManager, RouterInterface $router, TranslatorInterface $translator)
  {
    $this->logger = $logger;
    $this->entityManager = $entityManager;
    $this->router = $router;
    $this->translator = $translator;
  }

  public function getIdentifier(): string
  {
    return 'generic-external';
  }

  public static function getPaymentParameters()
  {
    return [];
  }

  public static function getFields()
  {
    return array(
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
    return $data;
  }

  public function onPreSetData(FormEvent $event)
  {
    $pratica = $event->getData();
    $options = $event->getForm()->getConfig()->getOptions();
    $helper = $options["helper"];

    $text = '<div class="row mt-5"><div class="col-sm-4"><strong>'.$this->translator->trans('pratica.numero').'</strong></div><div class="col-sm-8 d-inline-flex"><code>'.$pratica->getId().'</code></div></div>';
    $helper->setDescriptionText($text);

    /*
    try {
      if (isset($data['response']) && $data['response']['esito'] == 'OK') {
        $url = $this->getPaymentUrl($pratica);

      } else {

        $this->createPaymentRequest($pratica);
        $this->entityManager->flush();
        $url = $this->getPaymentUrl($pratica);
        $helper->setDescriptionText($this->generatePaymentButtons($pratica, $url));

      }

    } catch (\Exception $e) {
      $this->logger->error("Warning user about not being able to create a payment request for pratica " . $pratica->getId() . ' - ' . $e->getMessage());
      $helper->setDescriptionText("C'è stato un errore nella creazione della richiesta di pagamento, contatta l'assistenza.");
    }*/
  }

  /**
   * @param FormEvent $event
   */
  public function onPreSubmit(FormEvent $event)
  {

  }

  /**
   * @param Pratica $pratica
   * @param $url
   * @return string
   */
  private function generatePaymentButtons( Pratica $pratica, $url )
  {

    $buttons = '<div class="row mt-5"><div class="col-sm-4"><strong>'.$this->translator->trans('pratica.numero').'</strong></div><div class="col-sm-8 d-inline-flex"><code>'.$pratica->getId().'</code></div></div>';
    $buttons .= "<p class='mt-5'>".$this->translator->trans('gateway.mypay.redirect_text', ['%gateway_name%' => $pratica->getPaymentType()])."</p><div class='text-center mt-5'><a href='{$url['online_url']}' class='btn btn-lg btn-primary'>".$this->translator->trans('gateway.mypay.redirect_button')."</a></div>";

    return $buttons;
  }

  /**
   * @param Pratica $pratica
   * @return mixed
   */
  private function getPaymentUrl(Pratica $pratica)
  {
    $data = $pratica->getPaymentData();
    return $data['response'];
  }

  /**
   * @param Pratica $pratica
   * @return array
   * @throws \Exception|GuzzleException
   */
  private function createPaymentRequest(Pratica $pratica): array
  {
    $data = $pratica->getPaymentData();
    $gateway = $pratica->getPaymentType();
    if (!$gateway instanceof PaymentGateway) {
      throw new \Exception('Missing payment gateway');
    }

    $requestBody = $this->createPaymentRequestBody($pratica);
    $client = new Client();
    $request = new Request(
      'POST',
      $gateway->getUrl(),
      ['Content-Type' => 'application/json'],
      \json_encode($requestBody)
    );

    /** @var Response $response */
    $response = $client->send($request);

    if (!in_array($response->getStatusCode(), [Response::HTTP_OK, Response::HTTP_CREATED, Response::HTTP_ACCEPTED, Response::HTTP_NO_CONTENT])) {
      throw new \Exception("Error sending payment request: " . $response->getBody()->getContents());
    }

    $decoded = json_decode($response->getBody()->getContents(), true);

    /*
     {
         "status":"OK",
         "status_code":"000",
         "status_message":"Operazione completata con successo",
         "remote_id":"ef3bf5a5‐d9a9‐4b27‐b0d2‐aa87d6d9ae95",
         "iuv":"200910522634614",
         "codice_avviso":"001200910522634614",
         "online_url":"https://acardste.vaservices.eu/wallet/welcome?idSession=fc1723d2‐d40e‐4c2e‐89aec03f599d5894",
         "file_url":"https://acardste.vaservices.eu/wallet/welcome?idSession=fc1723d2‐d40e‐4c2e‐89aec03f599d5894"
      }
     */

    if ($decoded['status'] === 'KO') {
      $this->logger->error('Error response when creating a payment request', ['request' => $requestBody, 'response' => $decoded]);
      throw new \Exception("Unable to create a payment request.");
    }

    $data['request'] = $requestBody;
    $data['response'] = $decoded;
    $pratica->setPaymentData($data);
    return $decoded;
  }

  /**
   * @param Pratica $pratica
   * @return array
   * @throws \Exception
   */
  private function createPaymentRequestBody(Pratica $pratica)
  {
    $data = $pratica->getPaymentData();
    $paymentParameters = $pratica->getServizio()->getPaymentParameters();
    $paymentDayLifeTime = 90;

    $gateway = $pratica->getPaymentType();
    if (!$gateway instanceof PaymentGateway) {
      throw new \Exception('Missing payment gateway');
    }

    $paymentIdentifier = $gateway->getIdentifier();

    $amount = $data[PaymentDataType::PAYMENT_AMOUNT];if (!$amount) {
      throw new \Exception('Missing amount');
    }

    $description = $data[PaymentDataType::PAYMENT_DESCRIPTION];

    /** @var CPSUser $user */
    $user = $pratica->getUser();

    $provincia = $user->getProvinciaResidenza() ? $user->getProvinciaResidenza() : 'TN';
    if (strlen($provincia) > 2) {
      $provincia = substr($provincia, 0, 2);
    }

    /*
      {
       “amount”:10,
       “reason”:“string”,
       “type”:“string”,
       “tenant_id”:“string”,
       “order_id”:“”,
       “payment_split”:[
       ],
       “expiration_time”:null,
       “return_url”:“string”,
       “notify_url”:“string”,
       “remote_id”:“string”,
       “payer”:{
          “name”:“string”,
          “surname”:“string”,
          “fiscal_code”:“string”
       },
       “pagopa_payment_code”:null
      }
     */

    $request = array(
      'amount' => $amount,
      'reason' => $description,
      'type' => $paymentParameters['gateways'][$paymentIdentifier]['parameters']['identificativoTipoDovuto'],
      'tenant_id' => $pratica->getServizio()->getEnte()->getId(),
      'order_id' => $this->calculateIUDFromPratica($pratica),
      'remote_id' => $pratica->getId(),
      'notifyUrl' => $this->generateNotifyUrl($pratica),
      'return_url' => $this->generateCallbackUrl($pratica),
      'payer' => [
        'name' => $user->getNome(),
        'surname' => $user->getCognome(),
        'fiscal_code' => $this->getCodiceFiscale($pratica),
        'email' => $user->getEmail(),
        'address' => $user->getIndirizzoResidenza(),
        'postal_code' => $user->getCapResidenza(),
        'city' => $user->getCittaResidenza(),
        'county' => $provincia
      ],
      'pagopa_payment_code'=> $paymentParameters['gateways'][$paymentIdentifier]['parameters']['datiSpecificiRiscossione'] ?? '9/3300.1',
      'expiration_time' => (new \DateTime())->modify('+'.$paymentDayLifeTime.'days')->format('Y-m-d'),
      'payment_split' => []
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
        $request['payment_split'][]= $temp;
      }
    }
    return $request;
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
  public function generateCallbackUrl(Pratica $pratica): string
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
