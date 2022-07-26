<?php


namespace AppBundle\Form\Admin\Ente;


use AppBundle\BackOffice\BackOfficeInterface;
use AppBundle\Entity\Ente;
use AppBundle\Form\Base\BlockQuoteType;
use AppBundle\Model\DefaultProtocolSettings;
use AppBundle\Model\Gateway;
use AppBundle\Payment\Gateway\GenericExternalPay;
use AppBundle\Payment\GatewayCollection;
use AppBundle\Payment\PaymentDataInterface;
use AppBundle\Services\BackOfficeCollection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Translation\TranslatorInterface;


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

  /** @var TranslatorInterface */
  private $translator;

  /**
   *  @param TranslatorInterface $translator
   * @param EntityManagerInterface $entityManager
   * @param BackOfficeCollection $backOffices
   * @param GatewayCollection $gatewayCollection
   */
  public function __construct(
    TranslatorInterface $translator,
    EntityManagerInterface $entityManager,
    BackOfficeCollection $backOffices,
    GatewayCollection $gatewayCollection
)
  {
    $this->translator = $translator;
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
    $settings = new DefaultProtocolSettings();
    if ($ente->getDefaultProtocolSettings() != null) {
      $settings = DefaultProtocolSettings::fromArray($ente->getDefaultProtocolSettings());
    }

    $availablePaymentGateways = $this->gatewayCollection->getAvailablePaymentGateways();
    foreach ($availablePaymentGateways as $k => $g) {
      $gateways[$g['name']] = $k;
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
      'choice_attr' => function($choice, $key, $value) use ($availablePaymentGateways, $ente) {
        // adds a class like attending_yes, attending_no, etc
        $g = $availablePaymentGateways[$choice]['handler'];
        $isGenericExternalPay = $g instanceof GenericExternalPay;
        $attr = [];
        if ($isGenericExternalPay) {
          $attr['class'] = 'external-pay-choice';
          $attr['data-tenant'] = $ente->getId();
          $attr['data-identifier'] = $choice;
          $attr['data-url'] = $availablePaymentGateways[$choice]['url'];
        }
        return $attr;
      },
      'mapped' => false,
      'expanded' => true,
      'multiple' => true,
      'required' => false,
      'label' => 'ente.pagamenti.label',
    ]);

    /** @var PaymentDataInterface $g */
    foreach ($availablePaymentGateways as $key => $value) {

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
          'label' => $this->translator->trans('ente.pagamenti.panel_title',['%gateway_name%' => $value['name']])
        ]);

        foreach ($parameters as $k => $v) {
          $gatewaySubform->add($k, TextType::class, [
              'label' => $v,
              'required' => false,
              'data' => isset($selectedGatewaysParameters[$key][$k]) ? $selectedGatewaysParameters[$key][$k] : '',
              'mapped' => false
            ]
          );
        }
        $builder->add($gatewaySubform);
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
