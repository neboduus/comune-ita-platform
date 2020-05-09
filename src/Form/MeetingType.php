<?php

namespace App\Form;

use App\Entity\Calendar;
use App\Entity\Meeting;
use App\Services\MeetingService;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
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
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;

class MeetingType extends AbstractType
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var MeetingService
     */
    private $meetingService;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(EntityManagerInterface $entityManager, MeetingService $meetingService, TranslatorInterface $translator)
    {
        $this->em = $entityManager;
        $this->meetingService = $meetingService;
        $this->translator = $translator;
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
                'class' => 'App\Entity\CPSUser',
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
            ])
            ->addEventListener(FormEvents::SUBMIT, array($this, 'onSubmit'));
    }

    /**
     * @param FormEvent $event
     * @throws \Exception
     */
    public function onSubmit(FormEvent $event)
    {
        /** @var Meeting $meeting */
        $meeting = $event->getForm()->getData();

        if (!$this->meetingService->isSlotAvailable($meeting)) {
            $event->getForm()->addError(new FormError($this->translator->trans('meetings.error.slot_unavailable')));
        }
        if (!$this->meetingService->isSlotValid($meeting)) {
            $event->getForm()->addError(new FormError($this->translator->trans('meetings.error.slot_invalid')));
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'App\Entity\Meeting',
            'csrf_protection' => false
        ));
    }

    public function getBlockPrefix()
    {
        return 'app_bundle_meeting_type';
    }
}
