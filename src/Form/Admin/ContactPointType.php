<?php

namespace App\Form\Admin;

use App\Model\ExternalCalendar;
use App\Utils\FormUtils;
use App\Form\I18n\I18nDataMapperInterface;
use App\Form\I18n\I18nTextType;
use App\Form\I18n\AbstractI18nType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;
use Symfony\Contracts\Translation\TranslatorInterface;

class ContactPointType extends AbstractI18nType
{

  public function buildForm(FormBuilderInterface $builder, array $options)
  {
    // $this->createTranslatableMapper($builder, $options)
    //     ->add('title', I18nTextType::class, [
    //         'label' => 'contact_point.contact_point_title',
    //         'required' => false,
    //         'purify_html' => true
    //     ])
    // ;

    $builder
      ->add('name', TextType::class, [
        'label' => 'contact_point.contact_point_name',
        'required' => false,
        'purify_html' => true
      ])
      ->add('email', EmailType::class, [
        'label' => 'Email',
        'required' => false,
        'purify_html' => true
      ])
      ->add('url', UrlType::class, [
        'label' => 'Url',
        'required' => false,
        'purify_html' => true
      ])
      ->add('phoneNumber', TextType::class, [
        'label' => 'contact_point.phone_number',
        'required' => false,
        'purify_html' => true
      ])
      ->add('pec', EmailType::class, [
        'label' => 'Pec',
        'required' => false,
        'purify_html' => true
      ]);
  }

  public function configureOptions(OptionsResolver $resolver)
  {
    $resolver->setDefaults(array(
      'data_class' => 'App\Entity\ContactPoint',
    ));
  }
}
