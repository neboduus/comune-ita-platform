<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotNull;

class FeedbackMessagesFormType extends AbstractType
{
  /**
   * @param FormBuilderInterface $builder
   * @param array $options
   */
  public function buildForm(FormBuilderInterface $builder, array $options)
  {
    $builder
      ->add('status_draft', FeedbackMessageType::class, ['constraints' => [
        new NotNull(['message' => 'status_draft is required'])
      ]])
      ->add('status_pre_submit', FeedbackMessageType::class)
      ->add('status_submitted', FeedbackMessageType::class)
      ->add('status_registered', FeedbackMessageType::class)
      ->add('status_pending', FeedbackMessageType::class)
      ->add('status_complete', FeedbackMessageType::class)
      ->add('status_cancelled', FeedbackMessageType::class)
      ->add('status_withdraw', FeedbackMessageType::class);
  }

  /**
   * @param OptionsResolver $resolver
   */
  public function configureOptions(OptionsResolver $resolver)
  {
    $resolver->setDefaults(array(
      'data_class' => 'App\Model\FeedbackMessages',
      'csrf_protection' => false
    ));
  }

}
