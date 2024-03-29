<?php

namespace App\Form;

use App\Model\SubscriberMessage;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SubscriberMessageType extends AbstractType
{
  public function buildForm(FormBuilderInterface $builder, array $options)
  {
    $builder
      ->add('subject', TextType::class, [
        'label' => 'messages.subject_label',
        'required' => true,
      ])
      ->add('message', TextareaType::class, [
        'label' => 'messages.message_label',
        'required' => true,
      ])
      ->add('autoSend', CheckboxType::class, [
        'label' => 'messages.mail_to_me',
        'required' => false
      ]);
  }

  public function configureOptions(OptionsResolver $resolver)
  {
    $resolver->setDefaults([
      'data_class' => SubscriberMessage::class,
    ]);
  }

  public function getBlockPrefix()
  {
    return 'app_bundle_subscriber_message';
  }

  public function getName()
  {
    return 'subscriberMessage';
  }

}
