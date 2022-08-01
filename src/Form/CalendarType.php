<?php

namespace App\Form;

use App\Entity\Calendar;
use App\Entity\OperatoreUser;
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
use Symfony\Component\OptionsResolver\OptionsResolver;

class CalendarType extends AbstractType
{
  /**
   * @var EntityManagerInterface
   */
  private $em;
  /**
   * @var FeatureManagerInterface
   */
  private $featureManager;

  public function __construct(EntityManagerInterface $entityManager, FeatureManagerInterface $featureManager)
  {
    $this->em = $entityManager;
    $this->featureManager = $featureManager;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(FormBuilderInterface $builder, array $options)
  {
    $owners = $this->em
      ->createQuery(
        "SELECT user
             FROM App\Entity\User user
             WHERE (user INSTANCE OF App\Entity\OperatoreUser OR user INSTANCE OF App\Entity\AdminUser)"
      )->getResult();
    $owners = array_values($owners);

    $builder
      ->add('title', TextType::class, [
        'required' => true,
        'label' => false
      ])
      ->add('contact_email', EmailType::class, [
        'required' => false,
        'label' => false
      ])
      ->add('rolling_days', NumberType::class, [
        'required' => false,
        'empty_data' => Calendar::DEFAULT_ROLLING_DAYS,
        'label' => false
      ])
      ->add('minimum_scheduling_notice', ChoiceType::class, [
        'required' => false,
        'choices' => Calendar::MINIMUM_SCHEDULING_NOTICES_OPTIONS,
        'label' => false
      ])
      ->add('allow_cancel_days', NumberType::class, [
        'required' => false,
        'empty_data' => Calendar::DEFAULT_CANCEL_DAYS,
        'label' => false
      ])
      ->add('drafts_duration', NumberType::class, [
        'required' => false,
        'empty_data' => strval(Calendar::DEFAULT_DRAFT_DURATION/(60)),
        'label' => false,
      ])
      ->add('drafts_duration_increment', NumberType::class, [
        'required' => false,
        'empty_data' => strval(Calendar::DEFAULT_DRAFT_INCREMENT/(24*60*60)),
        'label' => false,
      ])
      ->add('allow_overlaps', CheckboxType::class, [
        'required' => false,
        'label' => 'calendars.allow_overlaps',
      ])
      ->add('is_moderated', CheckboxType::class, [
        'required' => false,
        'label' => 'calendars.is_moderated'
      ])
      ->add('owner', ChoiceType::class, [
        'choices' => $owners,
        'required' => true,
        'choice_label' => 'username',
        'choice_value' => 'id',
        'label' => false
      ])
      ->add('moderators', EntityType::class, [
        'class' => OperatoreUser::class,
        'label' => false,
        'expanded' => true,
        'multiple' => true,
      ])
      ->add('closing_periods', CollectionType::class, [
        'required' => false,
        'label' => false,
        'entry_type' => DateTimeIntervalType::class,
        'allow_add' => true
      ])
      ->add('location', TextareaType::class, [
        'required' => true,
        'label' =>false
      ])
      ->add('external_calendars', CollectionType::class, [
        'required' => false,
        'label' => false,
        'entry_type' => ExternalCalendarType::class,
        'allow_add' => true
      ]);

    if ($this->featureManager->isActive('feature_calendar_type')) {
      $builder->add('type', ChoiceType::class, [
        'label' => 'calendars.type.label',
        'choices' => Calendar::CALENDAR_TYPES
      ]);
    }

    $builder->addViewTransformer(new CallbackTransformer(
      function ($original) {
        $original->setDraftsDuration($original->getDraftsDuration()/60);
        $original->setDraftsDurationIncrement($original->getDraftsDurationIncrement()/(24*60*60));
        return $original;
      },
      function ($submitted) {
        $submitted->setDraftsDuration((int)$submitted->getDraftsDuration()*60);
        $submitted->setDraftsDurationIncrement((int)$submitted->getDraftsDurationIncrement()*(24*60*60));
        return clone $submitted;
      }
    ));
  }

  /**
   * {@inheritdoc}
   */
  public function configureOptions(OptionsResolver $resolver)
  {
    $resolver->setDefaults(array(
      'data_class' => 'App\Entity\Calendar',
      'csrf_protection' => false
    ));
  }

  /**
   * {@inheritdoc}
   */
  public function getBlockPrefix()
  {
    return 'App_calendar';
  }


}
