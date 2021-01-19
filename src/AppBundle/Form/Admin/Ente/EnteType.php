<?php


namespace AppBundle\Form\Admin\Ente;


use AppBundle\BackOffice\BackOfficeInterface;
use AppBundle\Entity\Ente;
use AppBundle\Entity\Servizio;
use AppBundle\Form\Base\BlockQuoteType;
use AppBundle\Model\DefaultProtocolSettings;
use AppBundle\Model\Gateway;
use AppBundle\Services\BackOfficeCollection;
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
   * @var EntityManager
   */
  private $em;

  /**
   * @var BackOfficeCollection
   */
  private $backOfficeCollection;

  public function __construct(EntityManagerInterface $entityManager, BackOfficeCollection $backOffices)
  {
    $this->em = $entityManager;
    $this->backOfficeCollection = $backOffices;
  }

  public function buildForm(FormBuilderInterface $builder, array $options)
  {
    /** @var Ente $ente */
    $ente = $builder->getData();
    $availableGateways = $this->em->getRepository('AppBundle:PaymentGateway')->findBy([
      'enabled' => 1
    ]);

    $settings = new DefaultProtocolSettings();
    if ($ente->getDefaultProtocolSettings() != null) {
      $settings = DefaultProtocolSettings::fromArray($ente->getDefaultProtocolSettings());
    }

    $gateways = [];
    foreach ($availableGateways as $g) {
      $gateways[$g->getName()] = $g->getIdentifier();
    }

    $selectedGateways = $ente->getGateways();
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

    $backOfficesData = [];
    /** @var BackOfficeInterface $b */
    foreach ($this->backOfficeCollection->getBackOffices() as $b) {
      $backOfficesData[$b->getName()] = $b->getPath();
    }

    $builder
      ->add('codice_meccanografico', TextType::class)
      ->add('site_url', TextType::class, [
        'label' => 'Link sul titolo di ogni pagina'
      ])
      ->add('codice_amministrativo', TextType::class)
      ->add('meta', TextareaType::class, ['required' => false])
      ->add(DefaultProtocolSettings::KEY, DefaultProtocolSettingsType::class, [
        'label' => 'Impostazioni di default per il protocollo',
        'mapped' => false,
        'data' => $settings
      ])
    ;

    if ( !empty($backOfficesData)) {
      $builder->add('backoffice_enabled_integrations', ChoiceType::class, [
        'choices' => $backOfficesData,
        'expanded' => true,
        'multiple' => true,
        'required' => false,
        'label' => 'Abilita integrazione con i backoffice',
      ]);
    }

    $builder->add('gateways', ChoiceType::class, [
      'data' => $selectedGatewaysIentifiers,
      'choices' => $gateways,
      'mapped' => false,
      'expanded' => true,
      'multiple' => true,
      'required' => false,
      'label' => 'Seleziona i metodi di pagamento disponibili per l\'ente',
    ]);



    foreach ($availableGateways as $g) {
      $parameters = $g->getFcqn()::getPaymentParameters();
      if (count($parameters) > 0) {

        $gatewaySubform = $builder->create($g->getIdentifier(), FormType::class, [
          'label' => false,
          'mapped' => false,
          'required' => false,
          'attr' => ['class' => 'gateway-form-type d-none']
        ]);

        $gatewaySubform->add($g->getIdentifier() . '_label', BlockQuoteType::class, [
          'label' => 'Parametri necessari per ' . $g->getName() . ' ( lasciare in binaco se si desidera impostare i valori a livello di servizio)'
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

    $builder->add('mailers', CollectionType::class, [
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