<?php

namespace App\Form;

use App\Model\FeedbackMessage;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FeedbackMessageType extends AbstractType
{
  public function buildForm(FormBuilderInterface $builder, array $options)
  {

    $builder
      ->add('name', TextType::class, [
        'label' => 'messages.status_request',
        'label_attr' => ['class' => 'placeholders'],
        'attr' => ['readonly' => 'readonly'],
        'required' => false
      ])
      ->add('is_active', CheckboxType::class, [
        'label' => "messages.enable_sending_message",
      ])
      ->add('trigger', HiddenType::class)
      ->add('subject', TextType::class, [
        'required'=> true,
        'label' => 'messages.subject_label'
      ])
      ->add('message', TextareaType::class, [
        'label' => "messages.message_label",
        'required' => true
      ]);
  }

  public function configureOptions(OptionsResolver $resolver)
  {
    $resolver->setDefaults(array(
      'data_class' => FeedbackMessage::class,
      'csrf_protection' => false,
    ));
  }

  public function getBlockPrefix(): string
  {
    return 'app_bundle_feedback_message_type';
  }
}
