<?php

namespace App\Form\Admin;

use App\Entity\Place;
use App\Form\Api\PostalAddressApiType;
use App\Form\I18n\AbstractI18nType;
use App\Form\I18n\I18nDataMapperInterface;
use App\Form\I18n\I18nTextareaType;
use App\Form\I18n\I18nTextType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class PlaceType extends AbstractI18nType
{

  public function buildForm(FormBuilderInterface $builder, array $options)
  {
    $this->createTranslatableMapper($builder, $options)
      ->add('name', I18nTextType::class, [
        'label' => 'general.nome',
        'required' => true,
        'purify_html' => true
      ])
      ->add('otherName', I18nTextareaType::class, [
        'label' => 'place.other_name',
        'required' => false,
        'purify_html' => true,
      ])
      ->add('shortDescription', I18nTextareaType::class, [
        'label' => 'servizio.short_description',
        'required' => false,
        'purify_html' => true,
      ])
      ->add('description', I18nTextareaType::class, [
        'label' => 'servizio.descrizione',
        'required' => false,
        'purify_html' => true,
      ])
      ->add('moreInfo', I18nTextareaType::class, [
        'label' => 'user_group.more_info',
        'required' => false,
        'purify_html' => true,
      ])
      ->add('identifier', I18nTextareaType::class, [
        'label' => 'place.identifier',
        'required' => false,
        'purify_html' => true,
      ])
    ;

    $builder
      ->add('latitude', TextareaType::class, [
        'label' => 'place.latitude',
        'required' => false,
        'purify_html' => true,
      ])
      ->add('longitude', TextareaType::class, [
        'label' => 'place.longitude',
        'required' => false,
        'purify_html' => true,
      ])
      ->add('topic', EntityType::class, [
        'class' => 'App\Entity\Categoria',
        'label' => 'servizio.categoria',
        'choice_label' => 'name',
        'required' => false
      ])
      ->add('geographicAreas', EntityType::class, [
        'class' => 'App\Entity\GeographicArea',
        'choice_label' => 'name',
        'label' => 'servizio.aree_geografiche',
        'attr' => ['style' => 'columns: 2;'],
        'required' => false,
        'multiple' => true,
        'expanded' => true
      ])
      ->add('coreContactPoint', ContactPointType::class, [
        'required' => false,
        'label' => 'user_group.core_contact_point',
      ])
      ->add('address', PostalAddressApiType::class, [
        'required' => false,
        'label' => 'place.address'
      ])
    ;
  }

  /**
   * {@inheritdoc}
   */
  public function configureOptions(OptionsResolver $resolver)
  {
    $resolver->setDefaults(array(
      'data_class' => Place::class,
      'csrf_protection' => false,
    ));
    $this->configureTranslationOptions($resolver);
  }
}
