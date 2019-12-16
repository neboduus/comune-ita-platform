<?php

namespace AppBundle\Payment\Gateway;


use AppBundle\Entity\Pratica;
use AppBundle\Form\Extension\TestiAccompagnatoriProcedura;
use AppBundle\Payment\AbstractPaymentData;
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
  const IMPORTO = 'importo';
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

  public function __construct(LoggerInterface $logger, MyPayService $myPayService, EntityManagerInterface $em, RouterInterface $router)
  {
    $this->logger = $logger;
    $this->myPayService = $myPayService;
    $this->em = $em;
    $this->router = $router;
  }

  public static function getPaymentParameters()
  {
    return [
      'category' => 'Specifica la categoria del servizio per il pagamento'
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

  public function onPreSetData(FormEvent $event)
  {
    $form = $event->getForm();
    $pratica = $event->getData();
    $options = $event->getForm()->getConfig()->getOptions();
    /** @var TestiAccompagnatoriProcedura $helper */
    $helper = $options["helper"];

    /** @var Pratica $pratica */
    $pratica->setPaymentData($this->myPayService->getSanitizedPaymentData($pratica));
    /**
     * What state are we in?
     * Payment failed?
     */
    try {
      $this->myPayService->checkPaymentForPratica($pratica);
      $this->em->persist($pratica);
      $this->em->flush();
      $data = $pratica->getPaymentData();
      switch ($data[MyPay::OVERALL_OUTCOME]) {
        case MyPay::ESITO_PENDING:
          $url = $this->myPayService->getUrlForCurrentPayment($pratica);
          $stepUrl = $this->myPayService->renderResumeUrlForPaymentCheck($pratica);

          $helper->setDescriptionText("Il pagamento ci risulta in corso.<br/><br/>Se hai già completato il pagamento ti preghiamo di aspettare qualche minuto e procedere al controllo dell'esito premendo il pulsante qui sotto (Servono alcuni minuti perché il pagamento venga registrato).<br/><a href='$stepUrl'><button type=\"button\" class=\"btn btn-info \" >Controlla l'esito</button></a><br/><br/>Se non hai ancora completato il pagamento e sei tornato qui per errore<br/><a href='$url'><button type='button' class='btn btn-info' >Torna al sistema di pagamento</button></a>");
          break;
        case MyPay::ESITO_ESEGUITO:
          $helper->setDescriptionText("Grazie per aver effettuato il pagamento, puoi procedere");
          break;
        default:
          $this->myPayService->createPaymentRequestForPratica($pratica);
          $this->em->flush();
          $url = $this->myPayService->getUrlForCurrentPayment($pratica);
          $helper->setDescriptionText("<a href='$url'><button type='button' class='btn btn-info' >Procedi con il pagamento</button></a>.<br/>
     Cliccando sul bottone qui sopra sarai reindirizzato verso l'infrastruttura di MyPay. A pagamento completato sarai riportato a questa pagina, dove potrai verificare l'esito del pagamento e completare la compilazione della pratica<br/>
     Il pagamento ha dei tempi tecnici di qualche minuto per il completamento e la verifica.");
          break;
      }

    } catch (\Exception $e) {
      $this->logger->error("Warning user about not being able to create a payment request for pratica " . $pratica->getId());
      $helper->setDescriptionText("C'è stato un errore nella creazione della richiesta di pagamento, contatta l'assistenza<br/><a href='mailto:a@b.b?subject=Errore nel pagamento della pratica " . $pratica->getId() . "'>Invia una email per ricevere assistenza</a>");

      /**
       * warn the user that we couldn't create the payment, ask him to contact support
       */
    }
  }

  public function onPreSubmit(FormEvent $event)
  {
    $form = $event->getForm();

    $form->getConfig()->getData();
    $pratica = $form->getData();

    /** @var Pratica $pratica */
    $pratica->setPaymentData($this->myPayService->getSanitizedPaymentData($pratica));

    $this->myPayService->checkPaymentForPratica($pratica);
    $this->em->persist($pratica);
    $this->em->flush();
    $data = $pratica->getPaymentData();
    switch ($data[MyPay::OVERALL_OUTCOME]) {
      case MyPay::ESITO_PENDING:
      case MyPay::ESITO_ESEGUITO:
        break;
      default:
        $form->addError(new FormError('Pagamento necessario'));
        break;
    }

  }
}
