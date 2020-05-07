<?php

namespace App\Form;

use App\Entity\Subscription;
use App\Entity\SubscriptionService;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SubscriptionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
      ->add('subscription_service', EntityType::class, [
        'class' => SubscriptionService::class,
        'choice_label' => 'id',
        'required' => true,
        'label' => 'Servizio a sottoscrizione'
      ])
      ->add('subscriber', SubscriberType::class, [
        'required' => true,
        'label' => 'Anagrafica',
      ]);
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
