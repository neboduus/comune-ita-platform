<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DateTimeIntervalType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
      $builder
        ->add('from_time', DateTimeType::class, [
          'widget' => 'single_text',
          'required' => true,
          'label' => 'Data di inizio',
        ])
        ->add('to_time', DateTimeType::class, [
          'widget' => 'single_text',
          'required' => true,
          'label' => 'Data di fine',
        ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
      $resolver->setDefaults(array(
        'data_class' => 'AppBundle\Model\DateTimeInterval',
        'csrf_protection' => false
      ));
    }

    public function getBlockPrefix()
    {
        return 'app_bundle_date_time_interval_type';
    }
}
