<?php

namespace AppBundle\Form;


use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class IOServiceParametersType extends AbstractType
{
  public function buildForm(FormBuilderInterface $builder, array $options)
  {
    $builder
      ->add('IOserviceId', TextType::class, ['label'=>'app_io.service_id', 'required' => false])
      ->add('primaryKey', TextType::class, ['label'=>'app_io.primary_key', 'required' => false])
      ->add('secondaryKey', TextType::class, ['label'=>'app_io.secondary_key', 'required' => false]);
  }

  /**
   * {@inheritdoc}
   */
  public function configureOptions(OptionsResolver $resolver)
  {
    $resolver->setDefaults(array(
    ));
  }
}
