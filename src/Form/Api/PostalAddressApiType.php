<?php

namespace App\Form\Api;

use App\Model\PostalAddress;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;


class PostalAddressApiType extends AbstractType
{
  /**
   * {@inheritdoc}
   */
  public function buildForm(FormBuilderInterface $builder, array $options)
  {

    $builder
      ->add('address_country', TextType::class, [
        'label' => 'place.addressCountry',
        'required' => true
      ])
      ->add('address_locality', TextType::class, [
        'label' => 'place.addressLocality',
        'required' => true
      ])
      ->add('address_region', TextType::class, [
        'label' => 'place.addressRegion',
        'required' => true
      ])
      ->add('post_office_box_number', TextType::class, [
        'label' => 'place.postOfficeBoxNumber',
        'required' => true
      ])
      ->add('postal_code', TextType::class, [
        'label' => 'place.postalCode',
        'required' => true
      ])
      ->add('street_address', TextType::class, [
        'label' => 'place.streetAddress',
        'required' => true
      ])
    ;
  }

}
