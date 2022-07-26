<?php


namespace AppBundle\Form\Admin\Servizio;


use AppBundle\Entity\Servizio;
use AppBundle\Form\Base\BlockQuoteType;
use AppBundle\Model\Gateway;
use AppBundle\Payment\Gateway\GenericExternalPay;
use AppBundle\Payment\GatewayCollection;
use AppBundle\Payment\PaymentDataInterface;
use AppBundle\Services\FormServerApiAdapterService;
use AppBundle\Services\PaymentService;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Translation\TranslatorInterface;

class PaymentDataType extends AbstractType
{

  const PAYMENT_AMOUNT = 'payment_amount';
  const PAYMENT_FINANCIAL_REPORT = 'payment_financial_report';
  const PAYMENT_DESCRIPTION = 'payment_description';

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
  /**
   * @var PaymentService
   */
  private $paymentService;
  /**
   * @var GatewayCollection
   */
  private $gatewayCollection;

  /** @var TranslatorInterface */
  private $translator;

  public function __construct(
    TranslatorInterface $translator,
    EntityManagerInterface $entityManager,
    FormServerApiAdapterService $formServerService,
    PaymentService $paymentService,
    GatewayCollection $gatewayCollection

  )
  {
    $this->em = $entityManager;
    $this->formServerService = $formServerService;
    $this->paymentService = $paymentService;
    $this->gatewayCollection = $gatewayCollection;
    $this->translator = $translator;
  }

  public function buildForm(FormBuilderInterface $builder, array $options)
  {

    $paymentsType = [
      'STATUS_PAYMENT_NOT_REQUIRED' => Servizio::PAYMENT_NOT_REQUIRED,
      'STATUS_PAYMENT_REQUIRED' => Servizio::PAYMENT_REQUIRED,
      'STATUS_PAYMENT_DEFERRED' => Servizio::PAYMENT_DEFERRED
    ];

    /** @var Servizio $service */
    $service = $builder->getData();
    $result = $this->formServerService->getForm($service->getFormIoId());

    if ($result['status'] == 'success' && isset($result['form']['components'])) {
      $this->arrayFlat($result['form']['components']);
    }

    $paymentParameters = $service->getPaymentParameters();
    $selectedGateways = isset($paymentParameters['gateways']) ? $paymentParameters['gateways'] : [];
    $selectedGatewaysIentifiers = $selectedGatewaysParameters = [];

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
    $normalizedTenantGateways = [];
    foreach ($tenantGateways as $s) {
      $normalizedTenantGateways [$s['identifier']] = $s;
    }
    $tenantGateways = $normalizedTenantGateways;

    $availableGateways = $this->gatewayCollection->getAvailablePaymentGateways();
    foreach ($tenantGateways as $g) {
      $identifier = $g['identifier'];
      if (isset($availableGateways[$identifier])) {
        $gatewaysChoice[$availableGateways[$identifier]['name']] = $identifier;
      }
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
        'label' => 'payment.type_payment',
        'data' => $paymentRequired,
        'choices' => $paymentsType
      ])
      ->add('total_amounts', MoneyType::class, [
        'mapped' => false,
        'required' => false,
        'data' => $fromForm ? 0 : $paymentAmount,
        'label' => $this->translator->trans('operatori.importo') . ($fromForm? $this->translator->trans('admin.payment_amount_description') : ''),
        'attr' => (($fromForm && $paymentAmount > 0) ? ['readonly' => 'readonly'] : [])
      ])
      ->add('gateways', ChoiceType::class, [
        'data' => $selectedGatewaysIentifiers,
        'choices' => $gatewaysChoice,
        'choice_attr' => function($choice, $key, $value) use ($availableGateways, $service) {
          // adds a class like attending_yes, attending_no, etc
          $g = $availableGateways[$choice]['handler'];
          $isGenericExternalPay = $g instanceof GenericExternalPay;
          $attr = [];
          if ($isGenericExternalPay) {
            $attr['class'] = 'external-pay-choice';
            $attr['data-tenant'] = $service->getEnte()->getId();
            $attr['data-service'] = $service->getId();
            $attr['data-identifier'] = $choice;
            $attr['data-url'] = $availableGateways[$choice]['url'];
          }
          return $attr;
        },
        'expanded' => true,
        'multiple' => true,
        'required' => false,
        'label' => 'steps.common.select_payment_gateway.label',
        'mapped' => false,
        //'placeholder' => 'gateway.none_placeholder',
      ]);

    /** @var PaymentDataInterface $g */
    foreach ($availableGateways as $key => $value) {

      $g = $value['handler'];
      $parameters = $g::getPaymentParameters();
      if (count($parameters) > 0) {
        $gatewaySubform = $builder->create($key, FormType::class, [
          'label' => false,
          'mapped' => false,
          'required' => false,
          'attr' => ['class' => 'gateway-form-type d-none']
        ]);

        $gatewaySubform->add($g->getIdentifier() . '_label', BlockQuoteType::class, [
          'label' => $this->translator->trans('payment.parameters_needed_for', ['%gateway%' => $value['name']])
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
      if ($data['payment_required'] == Servizio::PAYMENT_REQUIRED && $data['total_amounts'] <= 0 && !isset($this->fields[PaymentDataType::PAYMENT_AMOUNT])) {
        $event->getForm()->addError(
          new FormError($this->translator->trans('payment.error_amount'))
        );
      }

      if (!isset($data['gateways']) || empty($data['gateways'])) {
        $event->getForm()->addError(
          new FormError($this->translator->trans('payment.error_select_type'))
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
