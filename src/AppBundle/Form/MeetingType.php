<?php

namespace AppBundle\Form;

use AppBundle\Entity\Calendar;
use Doctrine\ORM\EntityManager;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;


class MeetingType extends AbstractType
{
  /**
   * @var EntityManager
   */
  private $em;

  public function __construct(EntityManager $entityManager)
  {
    $this->em = $entityManager;
  }

  public function buildForm(FormBuilderInterface $builder, array $options)
  {
    $statuses = ['Pending' => 0, 'Approved' => 1, 'Refused' => 2, 'Missed' => 3, 'Done' => 4, 'Cancelled' => 5];
    $builder
      ->add('calendar', EntityType::class, [
        'class' => Calendar::class,
        'label' => 'Calendario',
        'required' => true,
      ])
      ->add('user', EntityType::class, [
        'class' => 'AppBundle\Entity\CPSUser',
        'required' => false,
        'label' => 'User'
      ])
      ->add('email', EmailType::class, [
        'required' => false,
        'label' => 'Email'
      ])
      ->add('fiscal_code', TextType::class, [
        'required' => false,
        'label' => 'Codice fiscale'
      ])
      ->add('name', TextType::class, [
        'required' => false,
        'label' => 'Nome'
      ])
      ->add('phone_number', TelType::class, [
        'required' => false,
        'label' => 'Numero di telefono'
      ])
      ->add('from_time', DateTimeType::class, [
        'widget' => 'single_text',
        'required' => true,
        'label' => 'Orario di inizio'
      ])
      ->add('to_time', DateTimeType::class, [
        'widget' => 'single_text',
        'required' => true,
        'label' => 'Orario di fine'
      ])
      ->add('status', ChoiceType::class, [
        'label' => 'Stato',
        'required' => true,
        'choices' => $statuses
      ])
      ->add('user_message', TextareaType::class, [
        'required' => false,
        'label' => 'Messaggio'
      ])
      ->add('videoconference_link', UrlType::class, [
        'required' => false,
        'label' => 'Link videoconferenza'
      ]);
  }

  public function configureOptions(OptionsResolver $resolver)
  {
    $resolver->setDefaults(array(
      'data_class' => 'AppBundle\Entity\Meeting',
      'csrf_protection' => false
    ));
  }

  public function getBlockPrefix()
  {
    return 'app_bundle_meeting_type';
  }
}
