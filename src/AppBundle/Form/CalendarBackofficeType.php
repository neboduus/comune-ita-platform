<?php

namespace AppBundle\Form;

use AppBundle\Entity\OpeningHour;
use AppBundle\Entity\OperatoreUser;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use AppBundle\Form\DateTimeIntervalType;

class CalendarBackofficeType extends AbstractType
{
  /**
   * {@inheritdoc}
   */
  public function buildForm(FormBuilderInterface $builder, array $options)
  {
    $builder
      ->add('title', TextType::class, [
        'required' => true,
        'label' => 'Titolo del calendario'
      ])
      ->add('contact_email', EmailType::class, [
        'required' => false,
        'label' => 'Email di contatto'
      ])
      ->add('rolling_days', NumberType::class, [
        'required' => true,
        'label' => 'Numero di giorni entro il quale è possibile prenotare'
      ])
      ->add('is_moderated', CheckboxType::class, [
        'required'=>false,
        'label' => 'Richiede moderazione?'
      ])
      ->add('owner', EntityType::class, [
        'class' => 'AppBundle\Entity\OperatoreUser',
        'required' => true,
        'label' => 'Proprietario'
      ])
      ->add('moderators', EntityType::class, [
        'class' => OperatoreUser::class,
        'label' => 'Moderatori',
        'expanded' => true,
        'multiple' => true,
      ])
      ->add('closing_periods', CollectionType::class, [
        'label' => false,
        'entry_type' => DateTimeIntervalType::class,
        'entry_options' => ['label' => false],
        'prototype' => true,
        'allow_add' => true,
        'allow_delete' => true
      ])
      ->add('opening_hours', CollectionType::class, [
        'label' => false,
        'entry_type' => OpeningHourType::class,
        'entry_options' => ['label' => false],
        'prototype' => true,
        'allow_add' => true,
        'allow_delete' => true,
        'attr'=>['style'=>'display:none;']
      ])
      ->add('location', TextareaType::class, [
        'required' => true,
        'label' => 'Luogo dell\'appuntamento'
      ]);
  }

  /**
   * {@inheritdoc}
   */
  public function configureOptions(OptionsResolver $resolver)
  {
    $resolver->setDefaults(array(
      'data_class' => 'AppBundle\Entity\Calendar',
      'csrf_protection' => false
    ));
  }

  /**
   * {@inheritdoc}
   */
  public function getBlockPrefix()
  {
    return 'appbundle_calendar';
  }


}
