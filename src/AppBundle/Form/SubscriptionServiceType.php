<?php

namespace AppBundle\Form;

use AppBundle\Entity\SubscriptionService;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;

class SubscriptionServiceType extends AbstractType
{

  /**
   * @var EntityManager
   */
  private $em;

  /**
   * @var TranslatorInterface
   */
  private $translator;

  public function __construct(EntityManagerInterface $entityManager, TranslatorInterface $translator)
  {
    $this->em = $entityManager;
    $this->translator = $translator;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(FormBuilderInterface $builder, array $options)
  {
    $statuses = [
      'Pending' => SubscriptionService::STATUS_WAITING,
      'Attivo' => SubscriptionService::STATUS_ACTIVE,
      'Inattivo' => SubscriptionService::STATUS_UNACTIVE
    ];

    $builder
      ->add('name', TextType::class, [
        'required' => true,
        'label' => 'iscrizioni.nome'
      ])
      ->add('code', TextType::class, [
        'required' => true,
        'label' => 'iscrizioni.codice'
      ])
      ->add('description', TextareaType::class, [
        'required' => true,
        'label' => 'iscrizioni.descrizione'
      ])
      ->add('subscription_begin', DateType::class, [
        'widget' => 'single_text',
        'required' => true,
        'label' => 'iscrizioni.data_inizio_iscrizioni'
      ])
      ->add('subscription_end', DateType::class, [
        'widget' => 'single_text',
        'required' => true,
        'label' => 'iscrizioni.data_fine_iscrizioni'
      ])
      ->add('begin_date', DateType::class, [
        'widget' => 'single_text',
        'required' => true,
        'label' => 'iscrizioni.data_inizio'
      ])
      ->add('end_date', DateType::class, [
        'widget' => 'single_text',
        'required' => true,
        'label' => 'iscrizioni.data_fine'
      ])
      ->add('subscribers_limit', NumberType::class, [
        'required' => false,
        'label' => 'iscrizioni.limite'
      ])
      ->add('subscription_message', TextareaType::class, [
        'required' => false,
        'label' => 'iscrizioni.messaggio_iscrizione'
      ])
      ->add('begin_message', TextareaType::class, [
        'required' => false,
        'label' => 'iscrizioni.messaggio_inizio'
      ])
      ->add('end_message', TextareaType::class, [
        'required' => false,
        'label' => 'iscrizioni.messaggio_fine'
      ])
      ->add('status', ChoiceType::class, [
        'label' => 'iscrizioni.stato',
        'required' => true,
        'choices' => $statuses
      ])
      ->add('subscription_payments', CollectionType::class, [
        'label' => false,
        'entry_type' => SubscriptionPaymentType::class,
        'entry_options' => ['label' => false],
        'prototype' => true,
        'allow_add' => true,
        'allow_delete' => true
      ])
      ->add('tags', TagsType::class, [
        'label' => 'iscrizioni.tags',
        'required' => false
      ]);
    $builder->addEventListener(FormEvents::PRE_SUBMIT, array($this, 'onPreSubmit'));
  }


  public function onPreSubmit(FormEvent $event)
  {
    $data = $event->getData();
    /** @var SubscriptionService $subscriptionService */
    $subscriptionService = $event->getForm()->getData();

    $identifiers = [];

    // Check duplicates payment identifiers
    if (isset($data['subscription_payments'])) {
      foreach ($data['subscription_payments'] as $subscriptionPayment) {
        if (!in_array($subscriptionPayment['payment_identifier'], $identifiers)) {
          $identifiers[] = $subscriptionPayment['payment_identifier'];
        } else {
          try {
            $event->getForm()->addError(
              new FormError($this->translator->trans('backoffice.integration.subscription_service.duplicate_identifier', [
                "%identifier%" => $subscriptionPayment['payment_identifier'],
                "%date%" => (new \DateTime($subscriptionPayment['date']))->format('d/m/Y'),
                "%payment_reason%" => $subscriptionPayment['payment_reason']
              ]))
            );
          } catch (\Exception $e) {
            new FormError($this->translator->trans('backoffice.integration.subscription_service.invalid_date', [
              "%date%" => $subscriptionPayment['date']
            ]));
          }
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public
  function configureOptions(OptionsResolver $resolver)
  {
    $resolver->setDefaults(array(
      'data_class' => SubscriptionService::class,
      'csrf_protection' => false
    ));
  }

  /**
   * {@inheritdoc}
   */
  public
  function getBlockPrefix()
  {
    return 'appbundle_subscriptionservice';
  }
}
