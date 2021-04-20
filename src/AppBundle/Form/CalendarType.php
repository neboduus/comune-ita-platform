<?php

namespace AppBundle\Form;

use AppBundle\Entity\Calendar;
use AppBundle\Entity\OperatoreUser;
use Doctrine\ORM\EntityManagerInterface;
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

  public function __construct(EntityManagerInterface $entityManager)
  {
    $this->em = $entityManager;
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
        'empty_data' => Calendar::DEFAULT_DRAFT_DURATION,
        'label' => false,
      ])
      ->add('drafts_duration_increment', NumberType::class, [
        'required' => false,
        'empty_data' => Calendar::DEFAULT_DRAFT_INCREMENT,
        'label' => false,
      ])
      ->add('is_moderated', CheckboxType::class, [
        'required' => false,
        'label' => 'Richiede moderazione?'
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
