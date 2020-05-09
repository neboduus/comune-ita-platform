<?php

namespace App\Form;

use App\Entity\OperatoreUser;
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

class CalendarBackofficeType extends AbstractType
{
    /**
     * @var EntityManager
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
            'Un\'ora prima' => 1, 'Due ore prima' => 2, 'Quattro ore prima' => 4, 'Otto ore prima' => 8,
            'Un giorno prima' => 24, 'Due giorni prima' => 48, 'Tre giorni prima' => 72, 'Una settimana prima' => 168
        ];

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
                'label' => 'Titolo del calendario',
            ])
            ->add('id', TextType::class, [
                'label' => 'Identificativo calendario (Calendar ID)',
                'disabled' => true,
            ])
            ->add('contact_email', EmailType::class, [
                'required' => false,
                'label' => 'Email di contatto',
            ])
            ->add('rolling_days', NumberType::class, [
                'required' => true,
                'label' => false,
            ])
            ->add('minimum_scheduling_notice', ChoiceType::class, [
                'required' => true,
                'choices' => $minimumSchedulingNotices,
                'label' => 'Preavviso minimo per una prenotazione',
            ])
            ->add('allow_cancel_days', NumberType::class, [
                'required' => true,
                'label' => false,
            ])
            ->add('is_moderated', CheckboxType::class, [
                'required' => false,
                'label' => 'Richiede moderazione?',
            ])
            ->add('owner', ChoiceType::class, [
                'choices' => $owners,
                'choice_label' => 'username',
                'choice_value' => 'id',
                'required' => true,
                'label' => 'Proprietario'
            ])
            ->add('moderators', EntityType::class, [
                'class' => OperatoreUser::class,
                'label' => 'Moderatori',
                'expanded' => true,
                'multiple' => true,
            ])
            ->add('closing_periods', CollectionType::class, [
                'label' => false,
                'entry_type' => DateTimeIntervalType::class,
                'entry_options' => ['label' => false],
                'prototype' => true,
                'allow_add' => true,
                'allow_delete' => true
            ])
            ->add('opening_hours', CollectionType::class, [
                'label' => false,
                'entry_type' => OpeningHourType::class,
                'entry_options' => ['label' => false],
                'prototype' => true,
                'allow_add' => true,
                'allow_delete' => true,
                'attr' => ['style' => 'display:none;']
            ])
            ->add('location', TextareaType::class, [
                'required' => true,
                'label' => 'Luogo dell\'appuntamento'
            ])
            ->add('external_calendars', CollectionType::class, [
                'label' => false,
                'entry_type' => ExternalCalendarType::class,
                'entry_options' => ['label' => false],
                'prototype' => true,
                'allow_add' => true,
                'allow_delete' => true
            ]);
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
        return 'appbundle_calendar';
    }
}
