<?php


namespace AppBundle\Form\Admin\Ente;


use AppBundle\BackOffice\BackOfficeInterface;
use AppBundle\Entity\Ente;
use AppBundle\Entity\Servizio;
use AppBundle\Form\Base\BlockQuoteType;
use AppBundle\Model\DefaultProtocolSettings;
use AppBundle\Model\Gateway;
use AppBundle\Payment\GatewayCollection;
use AppBundle\Payment\PaymentDataInterface;
use AppBundle\Services\BackOfficeCollection;
use AppBundle\Services\PaymentService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;


class EnteType extends AbstractType
{

  /**
   * @var EntityManagerInterface
   */
  private $em;

  /**
   * @var BackOfficeCollection
   */
  private $backOfficeCollection;


  /**
   * @var GatewayCollection
   */
  private $gatewayCollection;

  /**
   * @param EntityManagerInterface $entityManager
   * @param BackOfficeCollection $backOffices
   * @param PaymentService $paymentService
   * @param GatewayCollection $gatewayCollection
   */
  public function __construct(EntityManagerInterface $entityManager, BackOfficeCollection $backOffices, GatewayCollection $gatewayCollection)
  {
    $this->em = $entityManager;
    $this->backOfficeCollection = $backOffices;
    $this->gatewayCollection = $gatewayCollection;
  }

  public function buildForm(FormBuilderInterface $builder, array $options)
  {

    $navigationTypes = [
      'ente.navigation_type.services' => Ente::NAVIGATION_TYPE_SERVICES,
      'ente.navigation_type.categories' => Ente::NAVIGATION_TYPE_CATEGORIES
    ];


    /** @var Ente $ente */
    $ente = $builder->getData();
    $handlers = $this->gatewayCollection->getHandlers();

    $settings = new DefaultProtocolSettings();
    if ($ente->getDefaultProtocolSettings() != null) {
      $settings = DefaultProtocolSettings::fromArray($ente->getDefaultProtocolSettings());
    }

    $availablePaymentGateways = $this->gatewayCollection->getAvailablePaymentGateways();
    $gateways = [];
    foreach ($availablePaymentGateways as $g) {
      $gateways[$g['name']] = $g['handler'];
    }

    $selectedGateways = $ente->getGateways();
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


    $backOfficesData = [];
    /** @var BackOfficeInterface $b */
    foreach ($this->backOfficeCollection->getBackOffices() as $b) {
      $backOfficesData[$b->getName()] = $b->getPath();
    }

    $builder
      ->add('codice_meccanografico', TextType::class, [
        'label' => 'ente.codice_meccanografico'
      ])
      ->add('site_url', TextType::class, [
        'label' => 'ente.site_url'
      ])
      ->add('codice_amministrativo', TextType::class, [
        'label' => 'ente.codice_amministrativo'
      ])
      ->add('navigation_type', ChoiceType::class, [
          'label' => 'ente.navigation_type.label',
          'choices' => $navigationTypes,
        ]
      )
      ->add('meta', TextareaType::class, ['required' => false, 'empty_data' => ""])
      ->add(DefaultProtocolSettings::KEY, DefaultProtocolSettingsType::class, [
        'label' => 'ente.impostazioni_protocollo',
        'mapped' => false,
        'data' => $settings
      ]);

    if (!empty($backOfficesData)) {
      $builder->add('backoffice_enabled_integrations', ChoiceType::class, [
        'choices' => $backOfficesData,
        'expanded' => true,
        'multiple' => true,
        'required' => false,
        'label' => 'ente.backoffices',
        'choice_attr' => function ($backoffice) {
          return ['class' => $backoffice];
        },
      ])
        ->add('linkable_application_meetings', CheckboxType::class, [
          'label' => 'ente.linkable_application_meetings.label',
          'required' => false
        ]);
    }

    $builder->add('gateways', ChoiceType::class, [
      'data' => $selectedGatewaysIentifiers,
      'choices' => $gateways,
      'mapped' => false,
      'expanded' => true,
      'multiple' => true,
      'required' => false,
      'label' => 'ente.pagamenti.label',
    ]);

    /** @var PaymentDataInterface $g */
    foreach ($handlers as $g) {
      if (isset($availablePaymentGateways[$g->getIdentifier()])) {
        $parameters = $g::getPaymentParameters();
        if (count($parameters) > 0) {

          $gatewaySubform = $builder->create($g->getIdentifier(), FormType::class, [
            'label' => false,
            'mapped' => false,
            'required' => false,
            'attr' => ['class' => 'gateway-form-type d-none']
          ]);

          $gatewaySubform->add($g->getIdentifier() . '_label', BlockQuoteType::class, [
            'label' => 'Parametri necessari per ' . $availablePaymentGateways[$g->getIdentifier()]['name'] . ' ( lasciare in bianco se si desidera impostare i valori a livello di servizio)'
          ]);

          foreach ($parameters as $k => $v) {
            $gatewaySubform->add($k, TextType::class, [
                'label' => $v,
                'required' => false,
                'data' => isset($selectedGatewaysParameters[$g->getIdentifier()][$k]) ? $selectedGatewaysParameters[$g->getIdentifier()][$k] : '',
                'mapped' => false
              ]
            );
          }
          $builder->add($gatewaySubform);
        }
      }
    }

    $builder->add('io_enabled', CheckboxType::class, [
      'label' => 'ente.app_io',
      'required' => false
    ]);

    $builder->add('mailers', CollectionType::class, [
      'label' => false,
      'entry_type' => MailerType::class,
      'allow_add' => true,
      'allow_delete' => true
    ]);

    $builder->addEventListener(FormEvents::PRE_SUBMIT, array($this, 'onPreSubmit'));
  }

  public function onPreSubmit(FormEvent $event)
  {
    /** @var Ente $ente */
    $ente = $event->getForm()->getData();
    $data = $event->getData();

    if (!isset($data['mailers'])) {
      $data['mailers'] = null;
      $event->setData($data);
    }

    if (isset($data[DefaultProtocolSettings::KEY])) {
      $ente->setDefaultProtocolSettings($data[DefaultProtocolSettings::KEY]);
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
    $ente->setGateways($gateways);
    $this->em->persist($ente);
  }

  public function getBlockPrefix()
  {
    return 'ente';
  }
}
