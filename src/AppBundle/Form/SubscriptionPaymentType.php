<?php

namespace AppBundle\Form;

use AppBundle\BackOffice\SubcriptionPaymentsBackOffice;
use AppBundle\BackOffice\SubcriptionsBackOffice;
use AppBundle\Entity\Servizio;
use AppBundle\Model\SubscriptionPayment;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;

class SubscriptionPaymentType extends AbstractType
{

  /**
   * @var EntityManagerInterface
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

  public function buildForm(FormBuilderInterface $builder, array $options)
  {
    $servicesData = [];
    $services = $this->em->getRepository(Servizio::class)->findAvailableForSubscriptionPaymentSettings();
    foreach ($services as $service) {
      $servicesData[$service->getName()] = $service->getId();
    }

    $types = [
      'backoffice.integration.subscription_service.payment.type_subscription_fee' => SubscriptionPayment::TYPE_SUBSCRIPTION_FEE,
      'backoffice.integration.subscription_service.payment.type_additional_fee' => SubscriptionPayment::TYPE_ADDITIONAL_FEE,
      'backoffice.integration.subscription_service.payment.type_optional' => SubscriptionPayment::TYPE_OPTIONAL,
    ];

    $builder
      ->add('type', ChoiceType::class, [
        'label' => 'backoffice.integration.subscription_service.payment.type',
        'choices' => $types,
        'empty_data' => SubscriptionPayment::TYPE_OPTIONAL,
        'expanded' =>true,
        'choice_attr' => function($choice, $key, $value) {
          // adds a class like attending_yes, attending_no, etc
          return ['class' => 'type_'.strtolower($value)];
        },
      ])
      ->add('date', DateType::class, [
        'widget' => 'single_text',
        'label' => 'backoffice.integration.subscription_service.payment.due_date'
      ])
      ->add('amount', MoneyType::class, [
        'label' => 'backoffice.integration.subscription_service.payment.amount'
      ])
      ->add('payment_identifier', TextType::class, [
        'label' => 'backoffice.integration.subscription_service.payment.identifier'
      ])
      ->add('payment_reason', TextType::class, [
        'label' => 'backoffice.integration.subscription_service.payment.reason'
      ])
      ->add('payment_service', ChoiceType::class, [
        'label' => 'backoffice.integration.subscription_service.payment.service_id',
        'choices' => $servicesData,
        'required' => true
      ])
      ->add('create_draft', CheckboxType::class, [
        'label' => 'backoffice.integration.subscription_service.payment.create_draft',
        'required' => false
      ])
      ->add('meta', TextareaType::class, [
        'label' => 'backoffice.integration.subscription_service.payment.meta',
        'required' => false,
        'empty_data' => "{}",
        # ignore summernote
        'attr' => ['class' => 'simple']
      ]);

    $builder->addEventListener(FormEvents::PRE_SUBMIT, array($this, 'onPreSubmit'));
  }

  /**
   * @throws \Doctrine\DBAL\Driver\Exception
   * @throws \Doctrine\DBAL\Exception
   */
  public function onPreSubmit(FormEvent $event)
  {
    $data = $event->getData();
    /** @var SubscriptionPayment $subscriptionPayment */
    $subscriptionPayment = $event->getForm()->getData();

    // Service check
    if (isset($data["payment_service"])) {
      try {
        /** @var Servizio $service */
        $service = $this->em->getRepository('AppBundle:Servizio')->find($data["payment_service"]);
        if (!$service) {
          // Missing service
          $event->getForm()->addError(
            new FormError($this->translator->trans('backoffice.integration.subscription_service.no_service',
              ["%service_id%" => $data["payment_service"]]))
          );
        }

        if ($service->getPaymentRequired() === Servizio::PAYMENT_NOT_REQUIRED) {
          // Payment is not enabled for service
          $event->getForm()->addError(
            new FormError($this->translator->trans('backoffice.integration.subscription_service.no_payment',
              ["%service_name%" => $service->getName()]))
          );
        }


        if ($data['type'] === SubscriptionPayment::TYPE_SUBSCRIPTION_FEE) {
          // Integration with subscription service payments is not enabled for service
          if (!in_array(SubcriptionsBackOffice::class, $service->getIntegrations())) {
            $event->getForm()->addError(
              new FormError($this->translator->trans('backoffice.integration.subscription_service.no_integration_subscription',
                ["%service_name%" => $service->getName()]))
            );
          }
        } else if (!in_array(SubcriptionPaymentsBackOffice::class, $service->getIntegrations())) {
          // Integration with subscription service is not enabled for service
          $event->getForm()->addError(
            new FormError($this->translator->trans('backoffice.integration.subscription_service.no_integration_additional_payment',
              ["%service_name%" => $service->getName()]))
          );
        }

      } catch (\Exception $exception) {
        // Error
        $event->getForm()->addError(
          new FormError($this->translator->trans('backoffice.integration.subscription_service.no_service',
            ["%service_id%" => $data["payment_service"]]
          ))
        );
      }
    }

    // Check if meta is a valid json
    if ($data["meta"] && !json_decode($data["meta"])) {
      $event->getForm()->addError(
        new FormError($this->translator->trans('backoffice.integration.subscription_service.invalid_meta'))
      );
    }

    // If identifier has been changed check that there are no applications
    if ($subscriptionPayment && $data['payment_identifier'] !== $subscriptionPayment->getPaymentIdentifier()) {
      $uniqueIdLike = trim($subscriptionPayment->getPaymentIdentifier() . '_' . $subscriptionPayment->getSubscriptionServiceCode() . '_%');
      $sql = "select id from pratica where servizio_id = '" . $service->getId() . "' and dematerialized_forms->'data'->>'unique_id' LIKE '" . $uniqueIdLike . "'";
      $stmt = $this->em->getConnection()->prepare($sql);
      $results = $stmt->executeQuery()->fetchAllAssociative();


      if (!empty($results)) {
        $event->getForm()->addError(
          new FormError($this->translator->trans('backoffice.integration.subscription_service.identifier_change_not_allowed', [
            '%payment_identifier%' => $subscriptionPayment->getPaymentIdentifier()
          ]))
        );
      }
    }
  }

  public function configureOptions(OptionsResolver $resolver)
  {
    $resolver->setDefaults([
      'data_class' => SubscriptionPayment::class,
      'csrf_protection' => false
    ]);
  }
}
