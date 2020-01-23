<?php

namespace AppBundle\Form;

use AppBundle\Entity\SubscriptionService;
use Doctrine\ORM\EntityManager;
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

  public function __construct(EntityManager $entityManager)
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
      ->add('subscriptionBegin', DateType::class, [
        'widget' => 'single_text',
        'required' => true,
        'label' => 'Data di inizio iscrizioni'
      ])
      ->add('subscriptionEnd', DateType::class, [
        'widget' => 'single_text',
        'required' => true,
        'label' => 'Data di fine iscrizioni'
      ])
      ->add('subscriptionAmount', MoneyType::class, [
        'required' => false,
        'label' => 'Quota di iscrizione'
      ])
      ->add('beginDate', DateType::class, [
        'widget' => 'single_text',
        'required' => true,
        'label' => 'Data di inizio'
      ])
      ->add('endDate', DateType::class, [
        'widget' => 'single_text',
        'required' => true,
        'label' => 'Data di fine'
      ])
      ->add('subscribersLimit', NumberType::class, [
        'required' => false,
        'label' => 'Limite di iscritti'
      ])
      ->add('subscriptionMessage', TextareaType::class, [
        'required' => false,
        'label' => 'Messaggio di iscrizione'
      ])
      ->add('beginMessage', TextareaType::class, [
        'required' => false,
        'label' => 'Messaggio di inizio'
      ])
      ->add('endMessage', TextareaType::class, [
        'required' => false,
        'label' => 'Messaggio di fine'
      ])
      ->add('status', ChoiceType::class, [
        'label' => 'Stato',
        'choices' => $statuses
      ])
      ->add('subscriptionPayments', CollectionType::class, [
        'label' => false,
        'entry_type' => SubscriptionPaymentType::class,
        'entry_options' => ['label' => false],
        'prototype' => true,
        'allow_add' => true
      ])
      ->add('tags', TagsType::class, [
        'label' => 'Tags'
      ]);
  }

  /**
   * {@inheritdoc}
   */
  public
  function configureOptions(OptionsResolver $resolver)
  {
    $resolver->setDefaults(array(
      'data_class' => SubscriptionService::class
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
