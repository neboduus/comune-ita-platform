<?php


namespace AppBundle\Form\Api;

use AppBundle\Entity\GeographicArea;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class GeographicAreaApiType extends AbstractType
{


  /**
   * {@inheritdoc}
   */
  public function buildForm(FormBuilderInterface $builder, array $options)
  {
    $builder
      ->add('name', TextType::class, [
        'required' => true
      ])
      ->add('description', TextareaType::class, [
        'required' => false
      ])
      ->add('geofence', TextType::class, [
          'required' => false,
        ]
      );
  }

  /**
   * {@inheritdoc}
   */
  public function configureOptions(OptionsResolver $resolver)
  {
    $resolver->setDefaults(array(
      'data_class' => GeographicArea::class,
      'csrf_protection' => false
    ));
  }
}
