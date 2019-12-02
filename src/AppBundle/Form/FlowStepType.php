<?php

namespace AppBundle\Form;

use AppBundle\Model\FlowStep;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FlowStepType extends AbstractType
{
  /**
   * {@inheritdoc}
   */
  public function buildForm(FormBuilderInterface $builder, array $options)
  {
    $builder
      ->add('identifier')
      ->add('title')
      ->add('description')
      ->add('guide')
      ->add('type');
    }

  /**
   * {@inheritdoc}
   */
  public function configureOptions(OptionsResolver $resolver)
  {
    $resolver->setDefaults(array(
      'data_class' => FlowStep::class,
      'csrf_protection' => false
    ));
  }
}
