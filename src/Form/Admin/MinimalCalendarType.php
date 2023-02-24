<?php

namespace App\Form\Admin;

use App\Entity\Calendar;
use App\Form\OpeningHourType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Security\Core\Security;

class MinimalCalendarType extends AbstractType
{

  /**
   * @var TranslatorInterface
   */
  private TranslatorInterface $translator;

  /**
   * @var Security
   */
  private Security $security;


  public function __construct(
    TranslatorInterface $translator,
    Security $security
  )
  {
    $this->translator = $translator;
    $this->security = $security;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(FormBuilderInterface $builder, array $options)
  {

    $builder
      ->add('opening_hours', CollectionType::class, [
        'label' => false,
        'entry_type' => OpeningHourType::class,
        'entry_options' => ['label' => false],
      ])
      ->add('location', TextareaType::class, [
        'required' => true,
        'label' => 'calendars.location'
      ])
      ->add('title', HiddenType::class)
      ->addEventListener(FormEvents::PRE_SUBMIT, [$this, 'onPreSubmit']);
  }

  public function onPreSubmit(FormEvent $event)
  {
    /** @var Calendar $calendar */
    $calendar = $event->getForm()->getData();
    $calendar->setUpdatedAt(new \DateTime());
    $user = $this->security->getUser();
    $calendar->setOwner($calendar->getOwner() ?? $user);
    $calendar->setIsModerated($calendar->getIsModerated() ?? false);
    $calendar->setModerators($calendar->getModerators() ?? [$user]);
    $event->getForm()->setData($calendar);

    if (!isset($event->getData()["allow_overlaps"])) {
      $calendar->setAllowOverlaps(false);
      $openingHours = $event->getData()['opening_hours'] ?? [];

      // Check if opening hours overlaps
      foreach ($openingHours as $index1 => $openingHour1) {
        foreach ($openingHours as $index2 => $openingHour2) {
          // Skip opening hours already analyzed
          if ($index2 > $index1) {
            $isDatesOverlapped = $openingHour1['start_date'] < $openingHour2['end_date'] && $openingHour1['end_date'] > $openingHour2['start_date'];
            $isTimesOverlapped = $openingHour1['begin_hour'] < $openingHour2['end_hour'] && $openingHour1['end_hour'] > $openingHour2['begin_hour'];
            $weekDaysOverlapped = array_intersect($openingHour1['days_of_week'], $openingHour2['days_of_week']);

            if ($isTimesOverlapped && $isDatesOverlapped && !empty($weekDaysOverlapped)) {
              // translate overlapped week days
              $days = array_map(function ($day) {
                return $this->translator->trans('calendars.opening_hours.weeks.week_day_' . $day);
              }, $weekDaysOverlapped);

              // Concatenation
              $daysStr = join(
                ' e ',
                array_filter(
                  array_merge(array(join(', ', array_slice($days, 0, -1))),
                    array_slice($days, -1)), 'strlen'));

              $event->getForm()->addError(new FormError(
                  $this->translator->trans('calendars.opening_hours.error.overlap', [
                    '%behinHour1%' => $openingHour1['begin_hour'],
                    '%endHour1%' => $openingHour1['end_hour'],
                    '%behinHour2%' => $openingHour2['begin_hour'],
                    '%endHour2%' => $openingHour2['end_hour'],
                    '%days%' => $daysStr
                  ]))
              );
            }
          }
        }
      }
    } else {
      $calendar->setAllowOverlaps(true);
    }
  }


  /**
   * {@inheritdoc}
   */
  public function configureOptions(OptionsResolver $resolver)
  {
    $resolver->setDefaults(array(
      'data_class' => Calendar::class,
      'allow_extra_fields' => true,
      'csrf_protection' => false
    ));
  }

  /**
   * {@inheritdoc}
   */
  public function getBlockPrefix(): string
  {
    return 'App_calendar';
  }


}
