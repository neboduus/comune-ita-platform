<?php

namespace App\Form\Api;

use App\Entity\Place;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;

class PlaceApiType extends AbstractType
{

  public function buildForm(FormBuilderInterface $builder, array $options)
  {
    $builder
      ->add('name', TextType::class, [
        'required' => true,
      ])
      ->add('other_name', TextType::class, [
        'required' => false,
        'purify_html' => true,
      ])
      ->add('short_description', TextType::class, [
        'required' => false,
        'purify_html' => true,
      ])
      ->add('description', TextareaType::class, [
        'required' => false,
        'purify_html' => true,
      ])
      ->add('more_info', TextareaType::class, [
        'label' => 'user_group.more_info',
        'required' => false,
        'purify_html' => true,
      ])
      ->add('identifier', TextType::class, [
        'required' => false,
        'purify_html' => true,
      ])
      ->add('latitude', TextType::class, [
        'required' => false,
        'purify_html' => true,
      ])
      ->add('longitude', TextType::class, [
        'required' => false,
        'purify_html' => true,
      ])
      ->add('topic_id', EntityType::class, [
        'class' => 'App\Entity\Categoria',
        'label' => 'servizio.categoria',
        'choice_label' => 'name',
        'required' => false,
        'mapped' => false
      ])
      ->add('geographic_areas_ids', EntityType::class, [
        'class' => 'App\Entity\GeographicArea',
        'label' => 'servizio.aree_geografiche',
        'required' => false,
        'mapped' => false
      ])
      ->add('core_contact_point', ContactPointApiType::class, [
        'required' => false,
        'label' => 'user_group.core_contact_point'
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
      'allow_extra_fields' => true,
      'csrf_protection' => false,
    ));
  }
}
