<?php

namespace App\Form\Api;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ContactPointApiType extends AbstractType
{

  public function buildForm(FormBuilderInterface $builder, array $options)
  {
    $builder
      ->add('name', TextType::class, [
        'required' => true,
      ])
      ->add('email', EmailType::class, [
        'label' => 'Email',
        'required' => false,
      ])
      ->add('url', UrlType::class, [
        'label' => 'Url',
        'required' => false,
      ])
      ->add('phone_number', TextType::class, [
        'label' => 'contact_point.phone_number',
        'required' => false,
      ])
      ->add('pec', EmailType::class, [
        'label' => 'Pec',
        'required' => false,
      ]);
  }

  public function configureOptions(OptionsResolver $resolver)
  {
    $resolver->setDefaults(array(
      'data_class' => 'App\Entity\ContactPoint',
    ));
  }
}
