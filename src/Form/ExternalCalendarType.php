<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ExternalCalendarType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
        ->add('name', TextType::class, [
          'required' => true,
          'label' => 'Nome',
        ])
        ->add('url', UrlType::class, [
          'required' => true,
          'label' => 'Url',
        ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
        'data_class' => 'App\Model\ExternalCalendar',
        'csrf_protection' => false
      ));
    }

    public function getBlockPrefix()
    {
        return 'app_bundle_external_calendar_type';
    }
}
