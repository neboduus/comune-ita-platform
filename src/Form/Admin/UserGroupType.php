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
use App\Form\Admin\MinimalCalendarType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class UserGroupType extends AbstractI18nType
{

  /**
   * @var TranslatorInterface
   */
  private TranslatorInterface $translator;

  /**
   * @param I18nDataMapperInterface $dataMapper
   * @param $locale
   * @param $locales
   * @param TranslatorInterface $translator
   */
  public function __construct(I18nDataMapperInterface $dataMapper, $locale, $locales, TranslatorInterface $translator)
  {
    parent::__construct($dataMapper, $locale, $locales);
    $this->translator = $translator;
  }

  /**
   * @throws \Exception
   */
  public function buildForm(FormBuilderInterface $builder, array $options)
  {
    /* @var UserGroup $userGroup**/
    $userGroup = $builder->getData();

    /** @var Calendar $calendar */
    $calendar = $builder->getData()->getCalendar() ?? new Calendar();

    $calendar->setTitle($calendar->getTitle() ?? $userGroup->getName());
    $calendar->setLocation($calendar->getLocation() ?? $userGroup->getCoreLocation() ? $userGroup->getCoreLocation()->getHumanReadableAddress() : '');

    if($calendar->getOpeningHours()->isEmpty()){
      $now = new \DateTime();
      $openingHours = (new OpeningHour())
        ->setIsModerated(false)
        ->setMeetingMinutes(30)
        ->setIntervalMinutes(0)
        ->setCreatedAt($now)
        ->setUpdatedAt($now)
        ->setStartDate($now)
        ->setEndDate(new \DateTime(date('Y') +1 . '-12-31'))
        ->setBeginHour(\DateTime::createFromFormat('H:i', Calendar::MIN_DATE))
        ->setEndHour(\DateTime::createFromFormat('H:i', Calendar::MAX_DATE))
        ->setDaysOfWeek([1, 2, 3, 4, 5])
        ->setName($this->translator->trans('user_group.hours'));
      $calendar->addOpeningHour($openingHours);
    }

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
      ])
    ;


    $builder
      ->add('topic', EntityType::class, [
        'class' => 'App\Entity\Categoria',
        'label' => 'servizio.categoria',
        'choice_label' => 'name',
        'required' => false
      ])
      ->add('manager', EntityType::class, [
        'class' => 'App\Entity\OperatoreUser',
        'label' => 'user_group.manager',
        'choice_label' => 'fullname',
        'required' => false
      ])
      ->add('users', EntityType::class, [
        'class' => 'App\Entity\OperatoreUser',
        'label' => 'user_group.users',
        'choice_label' => 'fullname',
        'multiple' => true,
        'required' => false,
        'attr' => ['style' => 'columns: 3;'],
        'expanded' => true
      ])
      ->add('services', EntityType::class, [
        'class' => 'App\Entity\Servizio',
        'label' => 'user_group.services',
        'choice_label' => 'fullname',
        'multiple' => true,
        'required' => false,
        'attr' => ['style' => 'columns: 2;'],
        'expanded' => true
      ])
      ->add('coreContactPoint', ContactPointType::class, [
        'required' => false,
        'label' => 'user_group.core_contact_point',
      ])
      // Utilizzo l'ApiType per un bug di inclusione di due form con translations attive
      ->add('coreLocation', PlaceApiType::class, [
        'required' => false,
        'label' => 'place.address'
      ])
      ->add('calendar', MinimalCalendarType::class, [
        'required' => false,
        'label' => false,
        'data' => $calendar
      ])
    ;
    $builder->addEventListener(FormEvents::PRE_SUBMIT, array($this, 'onPreSubmit'));
  }

  public function onPreSubmit(FormEvent $event)
  {
    $data = $event->getData();
    $data['coreContactPoint']['name'] = $data['name'][$this->getLocale()] ?? '';
    $data['coreLocation']['name'] = $data['name'][$this->getLocale()] ?? '';
    $data['calendar']['title'] = $data['name'][$this->getLocale()];
    $data['calendar']['location'] = $data['name'][$this->getLocale()];
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
}
