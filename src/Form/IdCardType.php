<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class IdCardType extends AbstractType
{
  public function buildForm(FormBuilderInterface $builder, array $options)
  {
    $builder
      ->add('numero', TextType::class, [
        'label'=>false, 'required'=>false
      ])
      ->add('comune_rilascio', TextType::class, [
        'label'=>false, 'required'=>false
      ])
      ->add('data_rilascio', DateType::class, [
        'widget' => 'single_text', 'required' => true, 'label' => false,'label_attr' => ['class' => 'active']
      ])
      ->add('data_scadenza', DateType::class, [
        'widget' => 'single_text', 'required' => true, 'label' => false,'label_attr' => ['class' => 'active']
      ]);

  }

  public function configureOptions(OptionsResolver $resolver)
  {
    $resolver->setDefaults(array(
      'data_class' => 'App\Model\IdCard',
      'csrf_protection' => false
    ));
  }

  public function getBlockPrefix()
  {
    return 'app_bundle_id_card_form';
  }
}
