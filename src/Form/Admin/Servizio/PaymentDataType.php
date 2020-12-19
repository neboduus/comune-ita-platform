<?php


namespace App\Form\Admin\Servizio;


use App\Entity\Servizio;
use App\Form\Base\BlockQuoteType;
use App\Form\PaymentParametersType;
use App\Model\Gateway;
use App\Payment\PaymentGatewayRegistry;
use App\Services\FormServerApiAdapterService;
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

  private $gatewayRegistry;

  public function __construct(EntityManagerInterface $entityManager, FormServerApiAdapterService $formServerService,  PaymentGatewayRegistry $gatewayRegistry)
  {
    $this->em = $entityManager;
    $this->formServerService = $formServerService;
    $this->gatewayRegistry = $gatewayRegistry;
  }

  public function buildForm(FormBuilderInterface $builder, array $options)
  {
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
    $gateways = $this->em->getRepository('App:PaymentGateway')->findBy([
      'identifier' => array_keys($tenantGateways)
    ]);
    $gatewaysChoice = [];
    foreach ($gateways as $g) {
      $gatewaysChoice[$g->getName()] = $g->getIdentifier();
    }

    $paymentRequired = $service->isPaymentRequired() || isset($this->fields[PaymentDataType::PAYMENT_AMOUNT]);
    $paymentAmount = 0;
    $fromForm = false;
    if (isset($this->fields[PaymentDataType::PAYMENT_AMOUNT]) && $this->fields[PaymentDataType::PAYMENT_AMOUNT]) {
      $paymentAmount = str_replace(',', '.', $this->fields[PaymentDataType::PAYMENT_AMOUNT]);
      $fromForm = true;
    } elseif (isset($paymentParameters['total_amounts']) && $paymentParameters['total_amounts']) {
      $paymentAmount = str_replace(',', '.', $paymentParameters['total_amounts']);
    }

    $builder
      ->add('payment_required', CheckboxType::class, [
        'required' => false,
        'data' => $paymentRequired
      ])
      ->add('total_amounts', MoneyType::class, [
        'mapped' => false,
        'required' => false,
        'data' => $paymentAmount,
        'label' => 'Importo' . ($fromForm? ' (Ereditato dal form)' : ''),
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
      $paymentGateway = $this->gatewayRegistry->get($g->getFcqn());
      $parameters = $paymentGateway::getPaymentParameters();
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

      if ($data['total_amounts'] <= 0) {
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
