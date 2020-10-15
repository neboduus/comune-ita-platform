<?php

namespace AppBundle\Form;

use AppBundle\Dto\Application;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ApplicationType extends AbstractType
{
  public function buildForm(FormBuilderInterface $builder, array $options)
  {
    $builder
      ->add('protocol_folder_number', TextType::class, [
        'label' => false,
        'required' => false
      ])
      ->add('protocol_number', TextType::class, [
        'label' => false,
        'required' => false
      ])
      ->add('protocol_document_id', TextType::class, [
        'label' => false,
        'required' => false
      ])
      ->add('outcome_protocol_number', TextType::class, [
        'label' => false,
        'required' => false
      ])
      ->add('outcome_protocol_document_id', TextType::class, [
        'label' => false,
        'required' => false
      ]);
  }

  public function configureOptions(OptionsResolver $resolver)
  {
    $resolver->setDefaults(array(
      'allow_extra_fields' => true,
      'data_class' => 'AppBundle\Dto\Application',
      'csrf_protection' => false
    ));
  }

  public function getBlockPrefix()
  {
    return 'app_bundle_application_type';
  }
}
