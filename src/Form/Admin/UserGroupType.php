<?php

namespace App\Form\Admin;

use App\Entity\UserGroup;
use App\Form\I18n\AbstractI18nType;
use App\Form\I18n\I18nDataMapperInterface;
use App\Form\I18n\I18nTextareaType;
use App\Form\I18n\I18nTextType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class UserGroupType extends AbstractI18nType
{
  /**
   * @var TranslatorInterface
   */
  private $translator;

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


  public function buildForm(FormBuilderInterface $builder, array $options)
  {

    $this->createTranslatableMapper($builder, $options)
      ->add('name', I18nTextType::class, [
        'label' => 'general.nome',
        'required' => true,
        'purify_html' => true
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
        'expanded' => true
      ])
    ;
    $builder->addEventListener(FormEvents::PRE_SUBMIT, array($this, 'onPreSubmit'));
  }

  /**
   * {@inheritdoc}
   */
  public function configureOptions(OptionsResolver $resolver)
  {
    $resolver->setDefaults(array(
      'data_class' => UserGroup::class,
      'csrf_protection' => false,
    ));
    $this->configureTranslationOptions($resolver);
  }

  public function onPreSubmit(FormEvent $event)
  {
    $data = $event->getData();
    if (isset($data['manager']) and !in_array($data['manager'], $data['users']))
    {
      $event->getForm()->addError(
        new FormError($this->translator->trans('user_group.manager_not_in_users'))
      );
    }

  }
}
