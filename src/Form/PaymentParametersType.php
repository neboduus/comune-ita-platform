<?php

namespace App\Form;

use App\Model\PaymentParameters;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PaymentParametersType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
      ->add('total_amounts')
      ->add('gateways', CollectionType::class, [
        'entry_type' => GatewayType::class,
        "allow_add" => true,
        "allow_delete" => true,
        'prototype'=>true,
        "label" => false
      ]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
      'data_class' => PaymentParameters::class,
      'csrf_protection' => false
    ));
    }
}
