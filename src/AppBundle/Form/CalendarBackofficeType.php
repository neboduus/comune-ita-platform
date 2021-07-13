<?php

namespace AppBundle\Form;

use AppBundle\Entity\Calendar;
use AppBundle\Entity\OperatoreUser;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Flagception\Manager\FeatureManagerInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;

class CalendarBackofficeType extends AbstractType
{

  /**
   * @var EntityManager
   */
  private $em;

  /**
   * @var FeatureManagerInterface
   */
  private $featureManager;

  /**
   * @var TranslatorInterface
   */
  private $translator;

  public function __construct(EntityManagerInterface $entityManager, FeatureManagerInterface $featureManager, TranslatorInterface $translator)
  {
    $this->em = $entityManager;
    $this->featureManager = $featureManager;
    $this->translator = $translator;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(FormBuilderInterface $builder, array $options)
  {
    $owners = $this->em
      ->createQuery(
        "SELECT user
             FROM AppBundle\Entity\User user
             WHERE (user INSTANCE OF AppBundle\Entity\OperatoreUser OR user INSTANCE OF AppBundle\Entity\AdminUser)"
      )->getResult();
    $owners = array_values($owners);

    $builder
      ->add('title', TextType::class, [
        'required' => true,
        'label' => 'calendars.title'
      ])
      ->add('id', TextType::class, [
        'label' => 'calendars.identifier',
        'disabled' => true,
      ])
      ->add('contact_email', EmailType::class, [
        'required' => false,
        'label' => 'calendars.contact_email'
      ])
      ->add('rolling_days', NumberType::class, [
        'required' => true,
        'label' => false
      ])
      ->add('minimum_scheduling_notice', ChoiceType::class, [
        'required' => true,
        'choices' => Calendar::MINIMUM_SCHEDULING_NOTICES_OPTIONS,
        'label' => 'calendars.minimum_scheduling_notice',
      ])
      ->add('allow_cancel_days', NumberType::class, [
        'required' => true,
        'label' => false,
      ])
      ->add('drafts_duration', NumberType::class, [
        'required' => false,
        'empty_data' => Calendar::DEFAULT_DRAFT_DURATION,
        'label' => "calendars.drafts_duration.label",
      ])
      ->add('drafts_duration_increment', NumberType::class, [
        'required' => false,
        'empty_data' => Calendar::DEFAULT_DRAFT_INCREMENT,
        'label' => "calendars.drafts_duration_increment.label",
      ])
      ->add('allow_overlaps', CheckboxType::class, [
        'required' => false,
        'label' => 'calendars.allow_overlaps',
      ])
      ->add('is_moderated', CheckboxType::class, [
        'required' => false,
        'label' => 'calendars.is_moderated',
      ])
      ->add('owner', ChoiceType::class, [
        'choices' => $owners,
        'required' => true,
        'choice_label' => 'username',
        'choice_value' => 'id',
        'label' => 'calendars.owner'
      ])
      ->add('moderators', EntityType::class, [
        'class' => OperatoreUser::class,
        'label' => false,
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
        'attr' => ['style' => 'display:none;']
      ])
      ->add('location', TextareaType::class, [
        'required' => true,
        'label' => 'calendars.location'
      ])
      ->add('external_calendars', CollectionType::class, [
        'label' => false,
        'entry_type' => ExternalCalendarType::class,
        'entry_options' => ['label' => false],
        'prototype' => true,
        'allow_add' => true,
        'allow_delete' => true
      ])
      ->addEventListener(FormEvents::PRE_SUBMIT, [$this, 'onPreSubmit']);

    if ($this->featureManager->isActive('feature_calendar_type')) {
      $builder->add('type', ChoiceType::class, [
        'label' => 'calendars.type.label',
        'choices' => Calendar::CALENDAR_TYPES
      ]);
    }

    $builder->addViewTransformer(new CallbackTransformer(
      function ($original) {
        $original->setDraftsDuration($original->getDraftsDuration() / 60);
        $original->setDraftsDurationIncrement($original->getDraftsDurationIncrement() / (24 * 60 * 60));
        return $original;
      },
      function ($submitted) {
        $submitted->setDraftsDuration((int)$submitted->getDraftsDuration() * 60);
        $submitted->setDraftsDurationIncrement((int)$submitted->getDraftsDurationIncrement() * (24 * 60 * 60));
        return clone $submitted;
      }
    ));
  }

  public function onPreSubmit(FormEvent $event)
  {
    /**
     * @var Calendar $calendar
     */
    $calendar = $event->getForm()->getData();

    if (!isset($event->getData()["allow_overlaps"])) {
      $calendar->setAllowOverlaps(false);
      $openingHours = isset($event->getData()['opening_hours']) ? $event->getData()['opening_hours'] : [];

      // Check if opening hours overlaps
      foreach ($openingHours as $index1 => $openingHour1) {
        foreach ($openingHours as $index2 => $openingHour2) {
          // Skip opening hours already analyzed
          if ($index2 > $index1) {
            $isDatesOverlapped = $openingHour1['start_date'] <= $openingHour2['end_date'] && $openingHour1['end_date'] >= $openingHour2['start_date'];
            $isTimesOverlapped = $openingHour1['begin_hour'] <= $openingHour2['end_hour'] && $openingHour1['end_hour'] >= $openingHour2['begin_hour'];
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
