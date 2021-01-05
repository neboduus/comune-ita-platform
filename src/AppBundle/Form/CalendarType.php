<?php

namespace AppBundle\Form;

use AppBundle\Entity\OperatoreUser;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
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
    $minimumSchedulingNotices = [
      'Un\'ora prima'=>1, 'Due ore prima'=>2, 'Quattro ore prima'=>4, 'Otto ore prima'=>8,
      'Un giorno prima'=>24, 'Due giorni prima'=>48, 'Tre giorni prima'=>72, 'Una settimana prima'=>168
    ];

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
        'label' => 'Titolo del calendario'
      ])
      ->add('contact_email', EmailType::class, [
        'required' => false,
        'label' => 'Email di contatto'
      ])
      ->add('rolling_days', NumberType::class, [
        'required' => true,
        'label' => 'Massino numero di giorni entro il quale è possibile prenotare'
      ])
      ->add('minimum_scheduling_notice', ChoiceType::class, [
        'required' => true,
        'choices' => $minimumSchedulingNotices,
        'label' => 'Minumo numero di ore entro il quale è possibile prenotare',
      ])
      ->add('allow_cancel_days', NumberType::class, [
        'required' => true,
        'label' => 'Numero minimo di giorni entro il quale è cancellare l\'appuntamento'
      ])
      ->add('is_moderated', CheckboxType::class, [
        'required' => true,
        'label' => 'Richiede moderazione?'
      ])
      ->add('owner', ChoiceType::class, [
        'choices' => $owners,
        'required' => true,
        'choice_label' => 'username',
        'choice_value' => 'id',
        'label' => 'Proprietario'
      ])
      ->add('moderators', EntityType::class, [
        'class' => OperatoreUser::class,
        'label' => false,
        'expanded' => true,
        'multiple' => true,
      ])
      ->add('closing_periods', CollectionType::class, [
        'required' => false,
        'label' => 'Periodi di chiusura',
        'entry_type' => DateTimeIntervalType::class,
        'allow_add' => true
      ])
      ->add('location', TextareaType::class, [
        'required' => true,
        'label' => 'Luogo dell\'appuntamento'
      ])
      ->add('external_calendars', CollectionType::class, [
        'required' => false,
        'label' => 'Calendari esterni',
        'entry_type' => ExternalCalendarType::class,
        'allow_add' => true
      ]);
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
