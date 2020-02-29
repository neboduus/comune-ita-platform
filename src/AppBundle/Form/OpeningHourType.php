<?php

namespace AppBundle\Form;

use AppBundle\Entity\Calendar;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OpeningHourType extends AbstractType
{
  /**
   * {@inheritdoc}
   */
  public function buildForm(FormBuilderInterface $builder, array $options)
  {
    $weekdays = ['Lunedì'=>1, 'Martedì'=>2, 'Mercoledì'=>3, 'Giovedì'=>4, 'Venerdì'=>5, 'Sabato'=>6, 'Domenica'=>7];

    $builder
      ->add('start_date', DateType::class, [
        'widget' => 'single_text',
        'required' => true,
        'label' => 'Data di inizio'
      ])
      ->add('end_date', DateType::class, [
        'widget' => 'single_text',
        'required' => true,
        'label' => 'Data di fine'
      ])
      ->add('days_of_week', ChoiceType::class, [
        'required' => false,
        'choices'=> $weekdays,
        'multiple' => true,
        'expanded' => true,
      ])
      ->add('begin_hour', TimeType::class, [
        'widget' => 'single_text',
        'required' => true,
        'label' => 'Orario di apertura'
      ])
      ->add('end_hour', TimeType::class, [
        'widget' => 'single_text',
        'required' => true,
        'label' => 'Orario di chiusura'
      ])
      ->add('meeting_minutes', NumberType::class, [
        'required' => true,
        'label' => 'Numero di minuti del meeting',
      ])
      ->add('meeting_queue', NumberType::class, [
        'required' => true,
        'label' => 'Numero di meeting paralleli',
      ]);
  }

  /**
   * {@inheritdoc}
   */
  public function configureOptions(OptionsResolver $resolver)
  {
    $resolver->setDefaults(array(
      'data_class' => 'AppBundle\Entity\OpeningHour',
      'csrf_protection' => false
    ));
  }

  /**
   * {@inheritdoc}
   */
  public function getBlockPrefix()
  {
    return 'appbundle_openinghour';
  }

}
