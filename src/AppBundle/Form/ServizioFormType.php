<?php

namespace AppBundle\Form;

use AppBundle\AppBundle;
use AppBundle\Entity\Categoria;
use AppBundle\Entity\Servizio;
use AppBundle\Services\FormServerApiAdapterService;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ServizioFormType extends AbstractType
{
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
      ->add('allow_reopening')
      ->add('allow_withdraw')
      ->add('workflow')
    ;
  }

  /**
   * @param OptionsResolver $resolver
   */
  public function configureOptions(OptionsResolver $resolver)
  {
    $resolver->setDefaults(array(
      'data_class' => 'AppBundle\Dto\Service',
      'csrf_protection' => false
    ));
  }

}
