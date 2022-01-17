<?php

namespace AppBundle\Form;

use AppBundle\Entity\Subscriber;
use AppBundle\Entity\Subscription;
use AppBundle\Entity\SubscriptionService;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;

class SubscriptionType extends AbstractType
{
  public function buildForm(FormBuilderInterface $builder, array $options)
  {
    $builder
      ->add('subscription_service', EntityType::class, [
        'class' => SubscriptionService::class,
        'required' => true,
        'constraints' => [
          new NotBlank(),
          new NotNull(),
        ],
      ])
      ->add('subscriber', EntityType::class, [
        'class' => Subscriber::class,
        'required' => true,
        'constraints' => [
          new NotBlank(),
          new NotNull(),
        ],
      ])
      ->add('related_cfs', CollectionType::class, [
        'entry_type' => TextType::class,
        "allow_add" => true,
        "allow_delete" => true,
        'prototype' => true,
        'required' => false,
        'entry_options' => [
          'constraints' => [new Length(16)],
        ],
      ]);;
  }

  public function configureOptions(OptionsResolver $resolver)
  {
    $resolver->setDefaults(array(
      'data_class' => Subscription::class,
      'csrf_protection' => false
    ));
  }

  public function getBlockPrefix()
  {
    return 'app_bundle_subscription_type';
  }
}
