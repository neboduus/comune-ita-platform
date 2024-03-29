<?php


namespace App\Form\Admin\Ente;


use App\BackOffice\BackOfficeInterface;
use App\Entity\Ente;
use App\Form\Base\BlockQuoteType;
use App\Form\I18n\AbstractI18nType;
use App\Form\I18n\I18nDataMapperInterface;
use App\Form\I18n\I18nJsonType;
use App\Form\I18n\I18nTextType;
use App\Model\DefaultProtocolSettings;
use App\Model\Gateway;
use App\Payment\Gateway\GenericExternalPay;
use App\Payment\GatewayCollection;
use App\Payment\PaymentDataInterface;
use App\Services\BackOfficeCollection;
use Doctrine\ORM\EntityManagerInterface;
use phpDocumentor\Reflection\Types\String_;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;


class EnteType extends AbstractI18nType
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
   * @param I18nDataMapperInterface $dataMapper
   * @param $locale
   * @param $locales
   * @param TranslatorInterface $translator
   * @param EntityManagerInterface $entityManager
   * @param BackOfficeCollection $backOffices
   * @param GatewayCollection $gatewayCollection
   */
  public function __construct(
    I18nDataMapperInterface $dataMapper,
    $locale,
    $locales,
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
    parent::__construct($dataMapper, $locale, $locales);
  }

  public function buildForm(FormBuilderInterface $builder, array $options)
  {

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

    $this->createTranslatableMapper($builder, $options)
      ->add("name", I18nTextType::class, [
        'label' => 'ente.nome'
      ])
      ->add('meta', I18nJsonType::class, [
        'label' => false,
        'required' => false,
        'empty_data' => "",
        //'attr' => ['class' => "d-none"],
      ]);

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

    $builder->add('is_satisfy_enabled', CheckboxType::class, [
      'label' => 'ente.satisfy_enabled',
      'data' => $ente->isSatisfyEnabled(),
      "mapped" => false,
      'required' => false
    ]);

    $builder->add('satisfy_entrypoint_id', TextType::class, [
      'label' => 'ente.satisfy_entrypoint_id',
      'required' => false,
      'help' => 'ente.satisfy_help_tenant'
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

    if(!isset($data['is_satisfy_enabled']) or !$data['is_satisfy_enabled']) {
      $data['satisfy_entrypoint_id'] = null;
      $event->setData($data);
    } elseif(!isset($data['satisfy_entrypoint_id']) or !$data['satisfy_entrypoint_id']){
      $event->getForm()->addError(
        new FormError($this->translator->trans('ente.satisfy_entrypoint_id_empty'))
      );
    }

    $this->em->persist($ente);
  }

  public function configureOptions(OptionsResolver $resolver)
  {
    $resolver->setDefaults(array(
      'data_class' => Ente::class,
      'csrf_protection' => false
    ));
    $this->configureTranslationOptions($resolver);
  }

  public function getBlockPrefix()
  {
    return 'ente';
  }

}
