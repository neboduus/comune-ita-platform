<?php

namespace App\Form;

use App\Model\SubscriptionPayment;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SubscriptionPaymentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
      ->add('date', DateType::class, [
        'widget' => 'single_text',
        'label' => 'Data'
      ])
      ->add('amount', MoneyType::class, [
        'label' => 'Importo'
      ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
      'data_class' => SubscriptionPayment::class,
      'csrf_protection' => false
    ]);
    }
}
