<?php

namespace AppBundle\Form;

use AppBundle\Entity\Meeting;
use AppBundle\Entity\OpeningHour;
use AppBundle\Services\MeetingService;
use DateTime;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\Constraints\GreaterThan;
use Symfony\Component\Validator\Constraints\LessThan;
use Symfony\Component\Validator\Constraints\LessThanOrEqual;

class OpeningHourType extends AbstractType
{
  /**
   * @var TranslatorInterface $translator
   */
  private $translator;

  public function __construct(TranslatorInterface $translator, EntityManagerInterface $entityManager, MeetingService $meetingService)
  {
    $this->translator = $translator;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(FormBuilderInterface $builder, array $options)
  {
    $maxDate = (new DateTime())->setTimestamp(mktime(0,0,0,1,0,date('Y')+2));

    $builder
      ->add('name', TextType::class, [
        'required' => true,
        'label' => 'Nome'
      ])
      ->add('start_date', DateType::class, [
        'widget' => 'single_text',
        'required' => true,
        'label' => 'calendars.opening_hours.start_date',
        'constraints' => [new LessThan([
          'propertyPath' => 'parent.all[end_date].data',
          'message' => $this->translator->trans('calendars.opening_hours.errors.less_than_end_date')
        ]),]
      ])
      ->add('end_date', DateType::class, [
        'widget' => 'single_text',
        'required' => true,
        'label' => 'calendars.opening_hours.end_date',
        'constraints' => [new GreaterThan([
          'propertyPath' => 'parent.all[start_date].data',
          'message' => $this->translator->trans('calendars.opening_hours.errors.greater_than_start_date')
        ]),
          new LessThanOrEqual([
            'value' => $maxDate->format('c'),
            'message' => $this->translator->trans('calendars.opening_hours.errors.end_date_limit', ['%max_date%' => $maxDate->format('d/m/Y')])
          ]),]
      ])
      ->add('days_of_week', ChoiceType::class, [
        'label' => 'calendars.opening_hours.days_of_week',
        'required' => false,
        'choices' => OpeningHour::WEEKDAYS,
        'multiple' => true,
        'expanded' => true,
      ])
      ->add('begin_hour', TimeType::class, [
        'widget' => 'single_text',
        'required' => true,
        'label' => 'calendars.opening_hours.begin_hour',
        'constraints' => [new LessThan([
          'propertyPath' => 'parent.all[end_hour].data',
          'message' => $this->translator->trans('calendars.opening_hours.errors.less_than_end_hour')
        ]),]
      ])
      ->add('end_hour', TimeType::class, [
        'widget' => 'single_text',
        'required' => true,
        'label' => 'calendars.opening_hours.end_hour',
        'constraints' => [new GreaterThan([
          'propertyPath' => 'parent.all[begin_hour].data',
          'message' => $this->translator->trans('calendars.opening_hours.errors.greater_than_begin_hour')
        ]),]
      ])
      ->add('is_moderated', CheckboxType::class, [
        'required' => false,
        'label' => 'calendars.opening_hours.is_moderated',
      ])
      ->add('meeting_minutes', IntegerType::class, [
        'required' => true,
        'label' => 'calendars.opening_hours.meeting_minutes',
      ])
      ->add('interval_minutes', IntegerType::class, [
        'required' => true,
        'label' => 'calendars.opening_hours.interval_minutes',
      ])
      ->add('meeting_queue', IntegerType::class, [
        'required' => true,
        'label' => 'calendars.opening_hours.meeting_queue',
      ])
      ->addEventListener(FormEvents::PRE_SUBMIT, [$this, 'onPreSubmit']);
  }

  public function onPreSubmit(FormEvent $event) {
    /**
     * @var OpeningHour $openingHour
     */
    $openingHour = $event->getForm()->getData();
    $data = $event->getData();

    if ($openingHour) {
      // Check if duration can be changed, i.e there are no scheduled meetings (past meetings are excluded)
      $intervalChanged = $openingHour->getIntervalMinutes() != $data['interval_minutes'];
      $durationChanged = $openingHour->getMeetingMinutes() != $data['meeting_minutes'];
      if ($durationChanged || $intervalChanged) {
        $canChange = true;
        $availableOn = new DateTime();
        foreach ($openingHour->getMeetings() as $meeting) {
          if ($meeting->getFromTime() >= $availableOn &&
            !in_array($meeting->getStatus(), [Meeting::STATUS_REFUSED, Meeting::STATUS_CANCELLED])) {
            $availableOn = $meeting->getToTime();
            $canChange = false;
          }
        }
        if (!$canChange) {
          $event->getForm()->addError(
            new FormError($this->translator->trans('calendars.opening_hours.error.cannot_change',
              ['next_availability'=> $availableOn->modify('+1days')->format('d/m/Y')]))
          );
        }
      }
    }
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
