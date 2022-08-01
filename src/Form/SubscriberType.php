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
        'label' => 'iscrizioni.subscribers.name',
        'disabled' => $options['is_edit']
      ])
      ->add('surname', TextType::class, [
        'required' => true,
        'label' => 'iscrizioni.subscribers.surname',
        'disabled' => $options['is_edit']
      ])
      ->add('date_of_birth', DateType::class, [
        'widget' => 'single_text',
        'required' => true,
        'label' => 'iscrizioni.subscribers.date_of_birth'
      ])
      ->add('place_of_birth', TextType::class, [
        'required' => true,
        'label' => 'iscrizioni.subscribers.place_of_birth'
      ])
      ->add('fiscal_code', TextType::class, [
        'required' => true,
        'label' => 'iscrizioni.subscribers.fiscal_code',
        'disabled' => $options['is_edit']
      ])
      ->add('address', TextType::class, [
        'required' => false,
        'label' => 'iscrizioni.subscribers.address'
      ])
      ->add('house_number', TextType::class, [
        'required' => false,
        'label' => 'iscrizioni.subscribers.house_number'
      ])
      ->add('municipality', TextType::class, [
        'required' => false,
        'label' => 'iscrizioni.subscribers.municipality'
      ])
      ->add('postal_code', TextType::class, [
        'required' => false,
        'label' => 'iscrizioni.subscribers.postal_code'
      ])
      ->add('email', EmailType::class, [
        'required' => true,
        'label' => 'iscrizioni.subscribers.email_address',
        'disabled' => $options['is_edit']
      ]);
  }

  public function configureOptions(OptionsResolver $resolver)
  {
    $resolver->setDefaults(array(
      'data_class' => Subscriber::class,
      'csrf_protection' => false,
      'is_edit' => false
    ));

  }

  public function getBlockPrefix()
  {
    return 'app_bundle_subscriber_type';
  }
}
