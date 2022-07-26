<?php

namespace AppBundle\Form;

use AppBundle\Entity\Calendar;
use AppBundle\Entity\Meeting;
use AppBundle\Entity\OpeningHour;
use AppBundle\Services\MeetingService;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;


class MeetingType extends AbstractType
{

  /**
   * @var MeetingService
   */
  private $meetingService;

  public function __construct(MeetingService $meetingService)
  {
    $this->meetingService = $meetingService;
  }

  public function buildForm(FormBuilderInterface $builder, array $options)
  {
    $statuses = ['Pending' => 0, 'Approved' => 1, 'Refused' => 2, 'Missed' => 3, 'Done' => 4, 'Cancelled' => 5];
    $builder
      ->add('calendar', EntityType::class, [
        'class' => Calendar::class,
        'label' => 'meetings.labels.calendar',
        'required' => true,
      ])
      ->add('opening_hour', EntityType::class, [
        'class' => OpeningHour::class,
        'label' => 'calendars.opening_hours.begin_hour',
        'required' => true,
      ])
      ->add('user', EntityType::class, [
        'class' => 'AppBundle\Entity\CPSUser',
        'required' => false,
        'label' => 'meetings.labels.user',
      ])
      ->add('opening_hour', EntityType::class, [
        'class' => 'AppBundle\Entity\OpeningHour',
        'required' => false,
        'label' => 'meetings.labels.opening_hour',
      ])
      ->add('opening_hour', EntityType::class, [
        'class' => 'AppBundle\Entity\OpeningHour',
        'required' => false,
        'label' => 'calendars.opening_hours.begin_hour',
      ])
      ->add('email', EmailType::class, [
        'required' => false,
        'label' => 'meetings.labels.email',
      ])
      ->add('fiscal_code', TextType::class, [
        'required' => false,
        'label' => 'meetings.labels.fiscal_code',
      ])
      ->add('name', TextType::class, [
        'required' => false,
        'label' => 'meetings.labels.name',
      ])
      ->add('phone_number', TelType::class, [
        'required' => false,
        'label' => 'meetings.labels.phone_number',
      ])
      ->add('from_time', DateTimeType::class, [
        'widget' => 'single_text',
        'required' => true,
        'label' => 'meetings.labels.from_time',
      ])
      ->add('to_time', DateTimeType::class, [
        'widget' => 'single_text',
        'required' => true,
        'label' => 'meetings.labels.to_time',
      ])
      ->add('status', ChoiceType::class, [
        'label' => 'meetings.labels.status',
        'required' => true,
        'choices' => $statuses,
      ])
      ->add('user_message', TextareaType::class, [
        'required' => false,
        'label' => 'meetings.labels.user_message',
      ])
      ->add('motivation_outcome', TextareaType::class, [
        'required' => false,
        'label' => 'meetings.labels.motivation_outcome',
      ])
      ->add('videoconference_link', UrlType::class, [
        'required' => false,
        'label' => 'meetings.labels.videoconference_link',
      ])
      ->add('draft_expiration', DateTimeType::class, [
        'widget' => 'single_text',
        'required' => false,
        'label' => 'meetings.labels.draft_expiration',
      ])
      /*->add('first_available_date', DateType::class, [
        'widget' => 'single_text',
        'required' => false,
      ])
      ->add('first_available_start_time', TimeType::class, [
        'widget' => 'single_text',
        'required' => false,
      ])
      ->add('first_available_end_time', TimeType::class, [
        'widget' => 'single_text',
        'required' => false,
      ])
      ->add('first_availability_updated_at', DateTimeType::class, [
        'widget' => 'single_text',
        'required' => false,
      ])*/
      ->addEventListener(FormEvents::SUBMIT, array($this, 'onSubmit'));
  }

  public function onSubmit(FormEvent $event)
  {
    /** @var Meeting $meeting */
    $meeting = $event->getForm()->getData();
    foreach ($this->meetingService->getMeetingErrors($meeting) as $error) {
      $event->getForm()->addError(new FormError($error));
    }
  }

  public function configureOptions(OptionsResolver $resolver)
  {
    $resolver->setDefaults(array(
      'data_class' => 'AppBundle\Entity\Meeting',
      'csrf_protection' => false,
    ));
  }

  public function getBlockPrefix()
  {
    return 'app_bundle_meeting_type';
  }
}
