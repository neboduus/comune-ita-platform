<?php


namespace AppBundle\Form\Admin\Servizio;


use AppBundle\Entity\Servizio;
use AppBundle\Form\Base\BlockQuoteType;
use AppBundle\Form\PaymentParametersType;
use AppBundle\Model\Gateway;
use Doctrine\ORM\EntityManager;
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

  /**
   * @var EntityManager
   */
  private $em;

  public function __construct(EntityManager $entityManager)
  {
    $this->em = $entityManager;
  }

  public function buildForm(FormBuilderInterface $builder, array $options)
  {
    /** @var Servizio $service */
    $service = $builder->getData();
    $paymentParameters = $service->getPaymentParameters();

    $selectedGateways = isset($paymentParameters['gateways']) ? $paymentParameters['gateways'] : [];

    $selectedGatewaysIentifiers = [];
    $selectedGatewaysParameters = [];

    foreach ($selectedGateways as $s) {
      if ($s instanceof Gateway) {
        $selectedGatewaysIentifiers []= $s->getIdentifier();
        $selectedGatewaysParameters[$s->getIdentifier()] = $s->getParameters();
      } else {
        $selectedGatewaysIentifiers []= $s['identifier'];
        $selectedGatewaysParameters [$s['identifier']]= $s['parameters'];
      }
    }

    $gateways = $this->em->getRepository('AppBundle:PaymentGateway')->findBy([
      'identifier' => $service->getEnte()->getGateways()
    ]);
    $tenantGateways = [];
    foreach ($gateways as $g) {
      $tenantGateways[$g->getName()]= $g->getIdentifier();
    }

    $builder
      ->add('payment_required', CheckboxType::class, [
        'required' => false
      ])
      ->add('total_amounts', MoneyType::class, [
        'mapped' => false,
        'required' => false,
        'data' => (isset($paymentParameters['total_amounts']) && $paymentParameters['total_amounts']) ? str_replace(',', '.', $paymentParameters['total_amounts']) : 0,
        'label' => 'Costo',
        //'disabled' => !$service->isPaymentRequired()
      ])
      ->add('gateways', ChoiceType::class, [
        'data' => $selectedGatewaysIentifiers,
        'choices' => $tenantGateways,
        'expanded' => true,
        'multiple' => true,
        'required' => false,
        'label' => 'Seleziona i metodi di pagamento che saranno disponbili per il servizio',
        'mapped' => false,
        //'disabled' => !$service->isPaymentRequired()
      ]);

      foreach ($gateways as $g) {
        $parameters = $g->getFcqn()::getPaymentParameters();
        if (count($parameters) > 0) {

          $gatewaySubform = $builder->create($g->getIdentifier(), FormType::class, [
            'label'    => false,
            'mapped'   => false,
            'required' => false,
            'attr' => ['class' => 'gateway-form-type d-none']
          ]);

          $gatewaySubform->add($g->getIdentifier().'_label', BlockQuoteType::class, [
            'label' => 'Parametri necessari per ' . $g->getName()
          ]);

          foreach ($parameters as $k => $v) {
            $gatewaySubform->add($k, TextType::class, [
                'label'    => $v,
                'data'     => isset($selectedGatewaysParameters[$g->getIdentifier()][$k]) ? $selectedGatewaysParameters[$g->getIdentifier()][$k] : '',
                'mapped'   => false,
                'required' => false
              ]
            );
          }

          $builder->add($gatewaySubform);
        }
      }

    //$builder->addEventListener(FormEvents::POST_SUBMIT, array($this, 'onPostSubmit'));
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

          $gateways[] = $gateway;
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

  public function getBlockPrefix()
  {
    return 'payment_data';
  }
}
