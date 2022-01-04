<?php

namespace AppBundle\Form;

use AppBundle\Dto\Service;
use AppBundle\Services\FormServerApiAdapterService;
use AppBundle\Services\Manager\BackofficeManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;

class ServizioFormType extends AbstractType
{
  /**
   * @var FormServerApiAdapterService
   */
  private $formServerService;
  /**
   * @var TranslatorInterface
   */
  private $translator;
  /**
   * @var BackofficeManager
   */
  private $backOfficeManager;

  /**
   * @var Container
   */
  private $container;

  public function __construct(Container $container, TranslatorInterface $translator, EntityManagerInterface $entityManager, FormServerApiAdapterService $formServerService, BackofficeManager $backOfficeManager)
  {
    $this->container = $container;
    $this->formServerService = $formServerService;
    $this->translator = $translator;
    $this->backOfficeManager = $backOfficeManager;
  }

  /**
   * @param FormBuilderInterface $builder
   * @param array $options
   */
  public function buildForm(FormBuilderInterface $builder, array $options)
  {
    $builder
      ->add('name')
      ->add('tenant', EntityType::class, [
        'class' => 'AppBundle\Entity\Ente',
        'choice_label' => 'name',
      ])
      ->add('topics')
      ->add('description')
      ->add('howto')
      ->add('who')
      ->add('special_cases')
      ->add('more_info')
      ->add('compilation_info')
      ->add('final_indications', TextareaType::class, [
        "label" => false,
        'empty_data' => 'La domanda è stata correttamente registrata, non ti sono richieste altre operazioni. Grazie per la tua collaborazione.',
      ])
      ->add('coverage', CollectionType::class, [
        'entry_type' => TextType::class,
        "allow_add" => true,
        "allow_delete" => true,
        'prototype' => true,
        "label" => false
      ])
      ->add('response_type')
      ->add('flow_steps', CollectionType::class, [
        'entry_type' => FlowStepType::class,
        "allow_add" => true,
        "allow_delete" => true,
        'prototype' => true,
        "label" => false
      ])
      ->add('protocol_required')
      ->add('protocol_handler')
      ->add('protocollo_parameters', TextareaType::class, ['empty_data' => ''])
      ->add('payment_required')
      ->add('payment_parameters', PaymentParametersType::class, [
        'data_class' => null
      ])
      ->add('integrations', IntegrationsType::class)
      ->add('io_parameters', IOServiceParametersType::class, [
        'required' => false,
        'data_class' => null
      ])
      ->add('sticky')
      ->add('status')
      ->add('access_level')
      ->add('login_suggested')
      ->add('scheduled_from', DateTimeType::class, [
        'required' => false,
        'empty_data' => null
      ])
      ->add('scheduled_to', DateTimeType::class, [
        'required' => false,
        'empty_data' => null
      ])
      ->add('service_group')
      ->add('shared_with_group')
      ->add('allow_reopening')
      ->add('allow_withdraw')
      ->add('allow_integration_request', CheckboxType::class, [
          'required' => false,
        ]
      )
      ->add('workflow')
      ->add('recipients_id', CollectionType::class, [
        'entry_type' => TextType::class,
        "allow_add" => true,
        "allow_delete" => true,
        'prototype' => true,
        "label" => false
      ])
      ->add('geographic_areas_id', CollectionType::class, [
        'entry_type' => TextType::class,
        "allow_add" => true,
        "allow_delete" => true,
        'prototype' => true,
        "label" => false
      ])
    ;

    $builder->addEventListener(FormEvents::PRE_SUBMIT, array($this, 'onPreSubmit'));
  }

  public function onPreSubmit(FormEvent $event)
  {
    /** @var Service $service */
    $service = $event->getForm()->getData();
    $data = $event->getData();

    if (isset($data['integrations']['trigger']) && $data['integrations']['trigger']) {
      $service->setIntegrations($data['integrations']);
      $event->getForm()->setData($service);

    } else {
      // No integration needed
      $service->setIntegrations(null);
      $event->getForm()->setData($service);
    }
  }


  private function arrayFlat($array, $prefix = '')
  {
    $result = array();
    foreach ($array as $key => $value) {
      if ($key == 'metadata' || $key == 'state') {
        continue;
      }
      $new_key = $prefix . (empty($prefix) ? '' : '.') . $key;

      if (is_array($value)) {
        $result = array_merge($result, $this->arrayFlat($value, $new_key));
      } else {
        $result[$new_key] = $value;
      }
    }
    return $result;
  }

  /**
   * @param OptionsResolver $resolver
   */
  public function configureOptions(OptionsResolver $resolver)
  {
    $resolver->setDefaults(array(
      'data_class' => 'AppBundle\Dto\Service',
      'allow_extra_fields' => true,
      'csrf_protection' => false
    ));
  }

}
