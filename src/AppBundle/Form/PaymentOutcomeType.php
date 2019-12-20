<?php

namespace AppBundle\Form;

use AppBundle\Model\PaymentOutcome;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PaymentOutcomeType extends AbstractType
{
  /**
   * {@inheritdoc}
   */
  public function buildForm(FormBuilderInterface $builder, array $options)
  {
    $builder
      ->add('status')
      ->add('status_code')
      ->add('status_message')
      ->add('data', TextareaType::class, []);
  }

  /**
   * {@inheritdoc}
   */
  public function configureOptions(OptionsResolver $resolver)
  {
    $resolver->setDefaults(array(
      'data_class' => PaymentOutcome::class,
      'csrf_protection' => false
    ));
  }
}
