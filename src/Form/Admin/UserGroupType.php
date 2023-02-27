<?php

namespace App\Form\Admin;

use App\Entity\Calendar;
use App\Entity\UserGroup;
use App\Form\Api\PlaceApiType;
use App\Form\I18n\AbstractI18nType;
use App\Form\I18n\I18nDataMapperInterface;
use App\Form\I18n\I18nTextareaType;
use App\Form\I18n\I18nTextType;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class UserGroupType extends AbstractI18nType
{
  private string $CRETE_NEW_CALENDAR_KEY = 'crete_new_calendar';

  /**
   * @var TranslatorInterface
   */
  private TranslatorInterface $translator;

  /**
   * @var EntityManagerInterface
   */
  private EntityManagerInterface $entityManager;

  /**
   * @var ArrayCollection
   */
  private $availableCalendars;

  /**
   * @param I18nDataMapperInterface $dataMapper
   * @param $locale
   * @param $locales
   * @param TranslatorInterface $translator
   * @param EntityManagerInterface $entityManager
   */
  public function __construct(I18nDataMapperInterface $dataMapper, $locale, $locales, TranslatorInterface $translator, EntityManagerInterface $entityManager)
  {
    parent::__construct($dataMapper, $locale, $locales);
    $this->translator = $translator;
    $this->entityManager = $entityManager;
    $this->availableCalendars = $this->entityManager->getRepository(Calendar::class)->findAll();
  }

  /**
   * @throws \Exception
   */
  public function buildForm(FormBuilderInterface $builder, array $options)
  {
    $calendars = ['Aggiungi calendario' => $this->CRETE_NEW_CALENDAR_KEY];
    foreach ($this->availableCalendars as $calendar){
      $calendars[$calendar->getTitle()] = $calendar->getId();
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
      ->add('calendar', ChoiceType::class, [
        'choices' => $calendars,
        //'data' => $calendar,
        'label' => 'user_group.calendar',
        'required' => false
      ])
    ;
    $builder->addEventListener(FormEvents::PRE_SUBMIT, array($this, 'onPreSubmit'));
  }

  public function onPreSubmit(FormEvent $event)
  {
    $data = $event->getData();
    $data['coreContactPoint']['name'] = $data['name'][$this->getLocale()] ?? '';
    $data['coreLocation']['name'] = $data['name'][$this->getLocale()] ?? '';

    if ($data['calendar'] == $this->CRETE_NEW_CALENDAR_KEY) {
      $isTitleSet = (array_key_exists('calendar', $data) and array_key_exists('title', $data['calendar']));
      if (!$isTitleSet or !$data['calendar']['title']) {
        $calendarTitle = $data['name'][$this->getLocale()];
        $titleAlreadyExists = $this->entityManager->getRepository('App\Entity\Calendar')->findOneBy(['title' => $calendarTitle]);
        if ($titleAlreadyExists) {
          $calendarTitle .= '-' . substr(uuid_create(), 0, 4);
        }
        $data['calendar']['title'] = $calendarTitle;
      }
      $isLocationSet = (array_key_exists('calendar', $data) and array_key_exists('location', $data['calendar']));
      if (!$isLocationSet or !$data['calendar']['location']) {
        $data['calendar']['location'] = $data['name'][$this->getLocale()];
      }
    } else if (uuid_is_valid($data['calendar'])){
      foreach ($this->availableCalendars as $calendar){
        if ($calendar->getId() == $data['calendar']){
          $data['calendar'] = $calendar->getId();
          break;
        }
      }
    } else {
      $data['calendar'] = null;
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
}
