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
use Symfony\Component\OptionsResolver\OptionsResolver;

class SubscriptionServiceType extends AbstractType
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
    $statuses = [
      'Pending' => SubscriptionService::STATUS_WAITING,
      'Attivo' => SubscriptionService::STATUS_ACTIVE,
      'Inattivo' => SubscriptionService::STATUS_UNACTIVE
    ];

    $builder
      ->add('name', TextType::class, [
        'required' => true,
        'label' => 'Nome'
      ])
      ->add('code', TextType::class, [
        'required' => true,
        'label' => 'Codice'
      ])
      ->add('description', TextareaType::class, [
        'required' => true,
        'label' => 'Descrizione'
      ])
      ->add('subscription_begin', DateType::class, [
        'widget' => 'single_text',
        'required' => true,
        'label' => 'Data di inizio iscrizioni'
      ])
      ->add('subscription_end', DateType::class, [
        'widget' => 'single_text',
        'required' => true,
        'label' => 'Data di fine iscrizioni'
      ])
      ->add('subscription_amount', MoneyType::class, [
        'required' => false,
        'label' => 'Quota di iscrizione'
      ])
      ->add('begin_date', DateType::class, [
        'widget' => 'single_text',
        'required' => true,
        'label' => 'Data di inizio'
      ])
      ->add('end_date', DateType::class, [
        'widget' => 'single_text',
        'required' => true,
        'label' => 'Data di fine'
      ])
      ->add('subscribers_limit', NumberType::class, [
        'required' => false,
        'label' => 'Limite di iscritti'
      ])
      ->add('subscription_message', TextareaType::class, [
        'required' => false,
        'label' => 'Messaggio di iscrizione'
      ])
      ->add('begin_message', TextareaType::class, [
        'required' => false,
        'label' => 'Messaggio di inizio'
      ])
      ->add('end_message', TextareaType::class, [
        'required' => false,
        'label' => 'Messaggio di fine'
      ])
      ->add('status', ChoiceType::class, [
        'label' => 'Stato',
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
        'label' => 'Tags',
        'required' => false
      ]);
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
