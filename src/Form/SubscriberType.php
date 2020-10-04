<?php

namespace App\Form;

use App\Entity\Subscriber;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;


class SubscriberType extends AbstractType
{
  public function buildForm(FormBuilderInterface $builder, array $options)
  {
    $builder
      ->add('name', TextType::class, [
        'required' => true,
        'label' => 'Nome'
      ])
      ->add('surname', TextType::class, [
        'required' => true,
        'label' => 'Cognome'
      ])
      ->add('date_of_birth', DateType::class, [
        'widget' => 'single_text',
        'required' => true,
        'label' => 'Nato/a il'
      ])
      ->add('place_of_birth', TextType::class, [
        'required' => true,
        'label' => 'A'
      ])
      ->add('fiscal_code', TextType::class, [
        'required' => true,
        'label' => 'Codice fiscale'
      ])
      ->add('address', TextType::class, [
        'required' => true,
        'label' => 'Indirizzo'
      ])
      ->add('house_number', TextType::class, [
        'required' => true,
        'label' => 'Numero civico'
      ])
      ->add('municipality', TextType::class, [
        'required' => true,
        'label' => 'Comune'
      ])
      ->add('postal_code', TextType::class, [
        'required' => true,
        'label' => 'CAP'
      ])
      ->add('email', EmailType::class, [
        'required' => true,
        'label' => 'Email'
      ]);
  }

  public function configureOptions(OptionsResolver $resolver)
  {
    $resolver->setDefaults(array(
      'data_class' => Subscriber::class,
      'csrf_protection' => false
    ));

  }

  public function getBlockPrefix()
  {
    return 'app_bundle_subscriber_type';
  }
}
