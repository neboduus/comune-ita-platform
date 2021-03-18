<?php


namespace AppBundle\Form\Admin\Servizio;


use AppBundle\Entity\Servizio;
use AppBundle\Form\Base\BlockQuoteType;
use AppBundle\Form\PaymentParametersType;
use AppBundle\Model\Gateway;
use AppBundle\Services\FormServerApiAdapterService;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use phpDocumentor\Reflection\Types\Self_;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class PaymentDataType extends AbstractType
{

  const PAYMENT_AMOUNT = 'payment_amount';
  const PAYMENT_FINANCIAL_REPORT = 'payment_financial_report';

  /**
   * @var EntityManager
   */
  private $em;

  /**
   * @var FormServerApiAdapterService
   */
  private $formServerService;

  /**
   * @var
   */
  private $fields = [];

  public function __construct(EntityManagerInterface $entityManager, FormServerApiAdapterService $formServerService)
  {
    $this->em = $entityManager;
    $this->formServerService = $formServerService;
  }

  public function buildForm(FormBuilderInterface $builder, array $options)
  {

    $paymentsType = [
      'Non richiesto' => Servizio::PAYMENT_NOT_REQUIRED,
      'Immediato' => Servizio::PAYMENT_REQUIRED,
      'Posticipato' => Servizio::PAYMENT_DEFERRED
    ];

    /** @var Servizio $service */
    $service = $builder->getData();
    $result = $this->formServerService->getForm($service->getFormIoId());

    if ($result['status'] == 'success' && isset($result['form']['components'])) {
      $this->arrayFlat($result['form']['components']);
    }

    $paymentParameters = $service->getPaymentParameters();

    $selectedGateways = isset($paymentParameters['gateways']) ? $paymentParameters['gateways'] : [];

    $selectedGatewaysIentifiers = [];
    $selectedGatewaysParameters = [];

    foreach ($selectedGateways as $s) {
      if ($s instanceof Gateway) {
        $selectedGatewaysIentifiers [] = $s->getIdentifier();
        $selectedGatewaysParameters[$s->getIdentifier()] = $s->getParameters();
      } else {
        $selectedGatewaysIentifiers [] = $s['identifier'];
        $selectedGatewaysParameters [$s['identifier']] = $s['parameters'];
      }
    }

    $tenantGateways = $service->getEnte()->getGateways();

    // Gateways abilitati nel tenant
    $gateways = $this->em->getRepository('AppBundle:PaymentGateway')->findBy([
      'identifier' => array_keys($tenantGateways)
    ]);
    $gatewaysChoice = [];
    foreach ($gateways as $g) {
      $gatewaysChoice[$g->getName()] = $g->getIdentifier();
    }

    $paymentRequired = $service->getPaymentRequired();
    if ($paymentRequired == Servizio::PAYMENT_NOT_REQUIRED && $paymentRequired) {
      $paymentRequired = Servizio::PAYMENT_REQUIRED;
    }
    $paymentAmount = 0;
    $fromForm = false;
    if (isset($this->fields[PaymentDataType::PAYMENT_AMOUNT]) && $this->fields[PaymentDataType::PAYMENT_AMOUNT]) {
      //$paymentAmount = str_replace(',', '.', $this->fields[PaymentDataType::PAYMENT_AMOUNT]);
      $fromForm = true;
    }

    if (isset($paymentParameters['total_amounts']) && $paymentParameters['total_amounts']) {
      $paymentAmount = str_replace(',', '.', $paymentParameters['total_amounts']);
    }

    $builder
      ->add('payment_required', ChoiceType::class, [
        'label' => 'Tipologia di Pagamento',
        'data' => $paymentRequired,
        'choices' => $paymentsType
      ])
      ->add('total_amounts', MoneyType::class, [
        'mapped' => false,
        'required' => false,
        'data' => $fromForm ? 0 : $paymentAmount,
        'label' => 'Importo' . ($fromForm? " (L'importo è determinato dal modulo tramite il valore del campo 'payment_amount')" : ''),
        'attr' => ($fromForm ? ['readonly' => 'readonly'] : [])
      ])
      ->add('gateways', ChoiceType::class, [
        'data' => $selectedGatewaysIentifiers,
        'choices' => $gatewaysChoice,
        'expanded' => true,
        'multiple' => true,
        'required' => false,
        'label' => 'Seleziona i metodi di pagamento che saranno disponbili per il servizio',
        'mapped' => false,
      ]);


    foreach ($gateways as $g) {
      $parameters = $g->getFcqn()::getPaymentParameters();
      if (count($parameters) > 0) {
        $gatewaySubform = $builder->create($g->getIdentifier(), FormType::class, [
          'label' => false,
          'mapped' => false,
          'required' => false,
          'attr' => ['class' => 'gateway-form-type d-none']
        ]);

        $gatewaySubform->add($g->getIdentifier() . '_label', BlockQuoteType::class, [
          'label' => 'Parametri necessari per ' . $g->getName()
        ]);

        foreach ($parameters as $k => $v) {
          $options = $this->setPaymentParameterOptions($g->getIdentifier(), $k, $v, $selectedGatewaysParameters, $tenantGateways);
          $gatewaySubform->add($k, TextType::class, $options);
        }

        $builder->add($gatewaySubform);
      }
    }

    $builder->addEventListener(FormEvents::PRE_SUBMIT, array($this, 'onPreSubmit'));

  }

  public function onPreSubmit(FormEvent $event)
  {
    /** @var Servizio $service */
    $service = $event->getForm()->getData();
    $data = $event->getData();

    if (isset($data['payment_required']) && $data['payment_required']) {

      // Eseguo il flush dell'oggetto altrimenti in caso di errore il form risulta disabilitato
      $service->setPaymentRequired($data['payment_required']);
      $this->em->persist($service);
      $this->em->flush($service);

      // Se è impostata la tipologia di pagamento istantaneo ma ho speciicato un valore <= 0 restituisco un errore
      if ($data['payment_required'] == Servizio::PAYMENT_REQUIRED && $data['total_amounts'] <= 0) {
        $event->getForm()->addError(
          new FormError('Devi inserire un costo maggiore di zero')
        );
      }

      if (!isset($data['gateways']) || empty($data['gateways'])) {
        $event->getForm()->addError(
          new FormError('Devi scegliere almeno un metodo di pagamento')
        );
      }

      $gateways = [];
      if (isset($data['gateways']) && !empty($data['gateways'])) {
        foreach ($data['gateways'] as $g) {
          $gateway = new Gateway();
          $gateway->setIdentifier($g);
          if (isset($data[$g])) {
            $gateway->setParameters($data[$g]);
          } else {
            $gateway->setParameters(null);
          }
          $gateways[$g] = $gateway;
        }
      }

      $paymentParameters = [];
      $paymentParameters['total_amounts'] = $data['total_amounts'];
      $paymentParameters['gateways'] = $gateways;
      $service->setPaymentParameters($paymentParameters);
    } else {
      $service->setPaymentParameters(null);
    }
    $this->em->persist($service);
  }

  /**
   * @param $gatewayIdentifier
   * @param $parameterIdentifier
   * @param $parameterLabel
   * @param $serviceParameters
   * @param $tenantParameters
   * @return array
   */
  private function setPaymentParameterOptions($gatewayIdentifier, $parameterIdentifier, $parameterLabel, $serviceParameters, $tenantParameters)
  {
    $value = '';
    $compiled = false;

    if (isset($tenantParameters[$gatewayIdentifier]['parameters'][$parameterIdentifier]) && !empty($tenantParameters[$gatewayIdentifier]['parameters'][$parameterIdentifier])) {
      $value = $tenantParameters[$gatewayIdentifier]['parameters'][$parameterIdentifier];
      $compiled = true;

    } elseif (isset($serviceParameters[$gatewayIdentifier][$parameterIdentifier])  && !empty($serviceParameters[$gatewayIdentifier][$parameterIdentifier])) {
      $value = $serviceParameters[$gatewayIdentifier][$parameterIdentifier];
    }

    $options = [
      'label' => $parameterLabel,
      'data' => $value,
      'mapped' => false,
      'required' => false
    ];

    if ($compiled) {
      $options['attr'] = ['readonly' => 'readonly'];
    }
    return $options;
  }

  /**
   * @param $array
   * @param string $prefix
   * @return array
   */
  private function arrayFlat($array)
  {
    $result = array();
    foreach ($array as $key => $value) {

      if (!is_array($value)) {
        if ($value === PaymentDataType::PAYMENT_AMOUNT || $value === PaymentDataType::PAYMENT_FINANCIAL_REPORT) {
          $this->fields[$value] = isset($array['defaultValue']) ? $array['defaultValue'] :[];
        }
      } else {
        $this->arrayFlat($value);
      }
    }
  }

  public function getBlockPrefix()
  {
    return 'payment_data';
  }
}
