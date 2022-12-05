<?php

namespace App\Form\Admin;

use App\Form\I18n\I18nTextType;
use App\Form\I18n\AbstractI18nType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ContactPointType extends AbstractI18nType
{

  public function buildForm(FormBuilderInterface $builder, array $options)
  {
    $this->createTranslatableMapper($builder, $options)
      ->add('name', I18nTextType::class, [
        'label' => 'contact_point.contact_point_name',
        'required' => true,
      ]);

    $builder
      ->add('email', EmailType::class, [
        'label' => 'Email',
        'required' => false,
      ])
      ->add('url', UrlType::class, [
        'label' => 'Url',
        'required' => false,
      ])
      ->add('phoneNumber', TextType::class, [
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
    $this->configureTranslationOptions($resolver);
  }
}
