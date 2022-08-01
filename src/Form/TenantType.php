<?php

namespace App\Form;

use App\BackOffice\BackOfficeInterface;
use App\Dto\Tenant;
use App\Model\Gateway;
use App\Services\BackOfficeCollection;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TenantType extends AbstractType
{

  /**
   * @var BackOfficeCollection
   */
  private $backOfficeCollection;

  public function __construct(BackOfficeCollection $backOffices)
  {
    $this->backOfficeCollection = $backOffices;
  }

  public function buildForm(FormBuilderInterface $builder, array $options)
  {
    $backOfficesData = [];
    /** @var BackOfficeInterface $b */
    foreach ($this->backOfficeCollection->getBackOffices() as $b) {
      $backOfficesData[$b->getName()] = $b->getPath();
    }

    $builder
      ->add('mechanographic_code', TextType::class, ['label' => false])
      ->add('administrative_code', TextType::class, ['label' => false])
      ->add('site_url', TextType::class, ['label' => false])
      ->add('meta', TextareaType::class, ['empty_data' => ""])
      ->add('io_enabled', CheckboxType::class)
      ->add('gateways', CollectionType::class, [
        'entry_type' => GatewayType::class,
        'allow_add' => true,
        'allow_delete' => true
      ]);

    if ( !empty($backOfficesData)) {
      $builder->add('backoffice_enabled_integrations', ChoiceType::class, [
        'choices' => $backOfficesData,
        'multiple' => true,
        'required' => false,
      ])
        ->add('linkable_application_meetings', CheckboxType::class);
    }

    $builder->addEventListener(FormEvents::PRE_SUBMIT, array($this, 'onPreSubmit'));
  }

  public function onPreSubmit(FormEvent $event)
  {
    /** @var Tenant $ente */
    $ente = $event->getForm()->getData();
    $data = $event->getData();

    $gateways = [];
    if (isset($data['gateways'])) {
      foreach ($data['gateways'] as $g) {
        $gateway = new Gateway();

        $gateway->setIdentifier($g["identifier"]);
        $gateway->setParameters($g["parameters"]);
        $gateways[$g["identifier"]] = $gateway;
      }
      $ente->setGateways($gateways);
    }
  }

  public function configureOptions(OptionsResolver $resolver)
  {
    $resolver->setDefaults(array(
      'data_class' => 'App\Dto\Tenant',
      'csrf_protection' => false,
    ));
  }

  public function getBlockPrefix()
  {
    return 'app_bundle_tenant_type';
  }
}
