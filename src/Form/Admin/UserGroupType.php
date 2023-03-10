<?php

namespace App\Form\Admin;

use App\Entity\Calendar;
use App\Entity\OpeningHour;
use App\Entity\UserGroup;
use App\Form\Api\PlaceApiType;
use App\Form\I18n\AbstractI18nType;
use App\Form\I18n\I18nDataMapperInterface;
use App\Form\I18n\I18nTextareaType;
use App\Form\I18n\I18nTextType;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Validator\Constraints\GreaterThan;
use Symfony\Component\Validator\Constraints\LessThan;
use Symfony\Contracts\Translation\TranslatorInterface;

class UserGroupType extends AbstractI18nType
{

  /**
   * @var TranslatorInterface
   */
  private TranslatorInterface $translator;

  /**
   * @var EntityManagerInterface
   */
  private EntityManagerInterface $entityManager;

  /**
   * @var Security
   */
  private Security $security;

  /**
   * @param I18nDataMapperInterface $dataMapper
   * @param $locale
   * @param $locales
   * @param TranslatorInterface $translator
   * @param EntityManagerInterface $entityManager
   * @param Security $security
   */
  public function __construct(
    I18nDataMapperInterface $dataMapper,
    $locale,
    $locales,
    TranslatorInterface $translator,
    EntityManagerInterface $entityManager,
    Security $security
  ) {
    parent::__construct($dataMapper, $locale, $locales);
    $this->translator = $translator;
    $this->entityManager = $entityManager;
    $this->security = $security;
  }

  /**
   * @throws \Exception
   */
  public function buildForm(FormBuilderInterface $builder, array $options)
  {

    /** @var UserGroup $userGroup */
    $userGroup = $builder->getData();

    $calendarChoices = [];
    $calendarsRepo = $this->entityManager->getRepository('App\Entity\Calendar');
    $calendars = $calendarsRepo->findAll();
    /** @var Calendar $calendar */
    foreach ($calendars as $calendar) {
      $calendarChoices[$calendar->getTitle()] = $calendar->getId();
    }
    $calendarChoices[$this->translator->trans('user_group.new_calendar')]= 'crete_new_calendar';

    $this->createTranslatableMapper($builder, $options)
      ->add('name', I18nTextType::class, [
        'label' => 'general.nome',
        'required' => true,
      ])
      ->add('shortDescription', I18nTextareaType::class, [
        'label' => 'servizio.short_description',
        'required' => false,
        'purify_html' => true,
      ])
      ->add('mainFunction', I18nTextareaType::class, [
        'label' => 'user_group.main_function',
        'required' => false,
        'purify_html' => true,
      ])
      ->add('moreInfo', I18nTextareaType::class, [
        'label' => 'user_group.more_info',
        'required' => false,
        'purify_html' => true,
      ]);


    $builder
      ->add('topic', EntityType::class, [
        'class' => 'App\Entity\Categoria',
        'label' => 'servizio.categoria',
        'choice_label' => 'name',
        'required' => false,
      ])
      ->add('manager', EntityType::class, [
        'class' => 'App\Entity\OperatoreUser',
        'label' => 'user_group.manager',
        'choice_label' => 'fullname',
        'required' => false,
      ])
      ->add('users', EntityType::class, [
        'class' => 'App\Entity\OperatoreUser',
        'label' => 'user_group.users',
        'choice_label' => 'fullname',
        'multiple' => true,
        'required' => false,
        'attr' => ['style' => 'columns: 3;'],
        'expanded' => true,
      ])
      ->add('services', EntityType::class, [
        'class' => 'App\Entity\Servizio',
        'label' => 'user_group.services',
        'choice_label' => 'fullname',
        'multiple' => true,
        'required' => false,
        'attr' => ['style' => 'columns: 2;'],
        'expanded' => true,
      ])
      ->add('coreContactPoint', ContactPointType::class, [
        'required' => false,
        'label' => 'user_group.core_contact_point',
      ])
      // Utilizzo l'ApiType per un bug di inclusione di due form con translations attive
      ->add('coreLocation', PlaceApiType::class, [
        'required' => false,
        'label' => 'place.address',
      ])
      ->add('calendar', ChoiceType::class, [
        'choices' => $calendarChoices,
        'data' => $userGroup->getCalendar() ? $userGroup->getCalendar()->getId() : null,
        'label' => 'user_group.calendar',
        'required' => false,
        'placeholder' => 'user_group.no_calendar',
        'mapped' => false
      ])
      ->add('days_of_week', ChoiceType::class, [
        'label' => 'calendars.opening_hours.days_of_week',
        'data' => [1,2,3,4,5],
        'required' => false,
        'choices' => OpeningHour::WEEKDAYS,
        'multiple' => true,
        'expanded' => true,
        'mapped' => false,
      ])
      ->add('begin_hour', TimeType::class, [
        'widget' => 'single_text',
        'data' => (new DateTime())->setTime(9, 0),
        'required' => false,
        'label' => 'calendars.opening_hours.begin_hour',
        'mapped' => false,
        'constraints' => [
          new LessThan([
            'propertyPath' => 'parent.all[end_hour].data',
            'message' => $this->translator->trans('calendars.opening_hours.errors.less_than_end_hour'),
          ]),
        ],
      ])
      ->add('end_hour', TimeType::class, [
        'widget' => 'single_text',
        'data' => (new DateTime())->setTime(13, 0),
        'required' => false,
        'mapped' => false,
        'label' => 'calendars.opening_hours.end_hour',
        'constraints' => [
          new GreaterThan([
            'propertyPath' => 'parent.all[begin_hour].data',
            'message' => $this->translator->trans('calendars.opening_hours.errors.greater_than_begin_hour'),
          ]),
        ],
      ]);
    $builder->addEventListener(FormEvents::PRE_SUBMIT, array($this, 'onPreSubmit'));
  }

  public function onPreSubmit(FormEvent $event)
  {
    $data = $event->getData();
    /** @var UserGroup $userGroup */
    $userGroup = $event->getForm()->getData();
    $userGroupName = $data['name'][$this->getLocale()];
    $data['coreContactPoint']['name'] = $userGroupName;
    $data['coreLocation']['name'] = $userGroupName;

    $calendarRepository = $this->entityManager->getRepository('App\Entity\Calendar');

    if ($data['calendar'] == 'crete_new_calendar') {
      $titleAlreadyExists = $calendarRepository->findOneBy(['title' => $userGroupName]);
      $uniqueEnding = '-'.substr(uuid_create(), 0, 4);
      $now = new \DateTime();

      $openingHours = (new OpeningHour())
        ->setIsModerated(false)
        ->setMeetingMinutes(30)
        ->setIntervalMinutes(0)
        ->setCreatedAt($now)
        ->setUpdatedAt($now)
        ->setStartDate($now)
        ->setEndDate(new \DateTime(date('Y') + 1 .'-12-31'))
        ->setBeginHour(\DateTime::createFromFormat('H:i', $data['begin_hour']))
        ->setEndHour(\DateTime::createFromFormat('H:i', $data['end_hour']))
        ->setDaysOfWeek($data['days_of_week'])
        ->setName($this->translator->trans('user_group.hours'));

      $newCalendar = (new Calendar())
        ->setTitle($titleAlreadyExists ? $userGroupName.$uniqueEnding : $userGroupName)
        ->setLocation($userGroupName)
        ->addOpeningHour($openingHours)
        ->setOwner($this->security->getUser());

      $this->entityManager->persist($newCalendar);
      $userGroup->setCalendar($newCalendar);
      $this->entityManager->flush();
    } else if (Uuid::isValid($data['calendar'])) {
      $calendar = $calendarRepository->find($data['calendar']);
      if ($calendar instanceof Calendar) {
        $userGroup->setCalendar($calendar);
        $this->entityManager->flush();
      }
    }

    $event->setData($data);
  }

  /**
   * {@inheritdoc}
   */
  public function configureOptions(OptionsResolver $resolver)
  {
    $resolver->setDefaults(array(
      'data_class' => UserGroup::class,
    ));
    $this->configureTranslationOptions($resolver);
  }

  private function getUniqueCalendarName($userGroupName)
  {

  }
}
