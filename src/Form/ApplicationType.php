<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ApplicationType extends AbstractType
{
  public function buildForm(FormBuilderInterface $builder, array $options)
  {
    $builder
      ->add('protocol_folder_number', TextType::class, ['label'=>false])
      ->add('protocol_folder_code', TextType::class, ['label'=>false])
      ->add('protocol_number', TextType::class, ['label'=>false])
      ->add('protocol_document_id', TextType::class, ['label'=>false])
      ->add('protocolled_at', DateTimeType::class, [
        'widget' => 'single_text',
        'required' => false,
        'empty_data' => ''
      ])
      ->add('outcome_protocol_number', TextType::class, ['label'=>false])
      ->add('outcome_protocol_document_id', TextType::class, ['label'=>false])
      ->add('outcome_protocolled_at', DateTimeType::class, [
        'widget' => 'single_text',
        'required' => false,
        'empty_data' => ''
      ]);
  }

  public function configureOptions(OptionsResolver $resolver)
  {
    $resolver->setDefaults(array(
      'data_class' => 'App\Dto\Application',
      'csrf_protection' => false,
      'allow_extra_fields' => true
    ));
  }

  public function getBlockPrefix()
  {
    return 'app_bundle_application_type';
  }
}
