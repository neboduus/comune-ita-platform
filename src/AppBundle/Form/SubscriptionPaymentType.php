<?php

namespace AppBundle\Form;

use AppBundle\BackOffice\SubcriptionPaymentsBackOffice;
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
    $builder
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
      # Fixme:aggiungere scelta del servizio dinamica
      ->add('payment_service', TextType::class, [
        'label' => 'backoffice.integration.subscription_service.payment.service_id'
      ])
      ->add('required', CheckboxType::class, [
        'label' => 'backoffice.integration.subscription_service.payment.required',
        'required' => false
      ])
      ->add('create_draft', CheckboxType::class, [
        'label' => 'backoffice.integration.subscription_service.payment.create_draft',
        'required' => false
      ])
      ->add('meta', TextareaType::class, [
        'label' => 'backoffice.integration.subscription_service.payment.meta',
        'required' => false,
        'empty_data'=>"{}",
        # ignore summernote
        'attr'=>['class' => 'simple']
      ]);

    $builder->addEventListener(FormEvents::PRE_SUBMIT, array($this, 'onPreSubmit'));
  }

  public function onPreSubmit(FormEvent $event)
  {
    $data = $event->getData();
    /** @var SubscriptionPayment $subscriptionPayment */
    $subscriptionPayment = $event->getForm()->getData();

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

        if (!$service->getIntegrations() || !in_array(SubcriptionPaymentsBackOffice::class, $service->getIntegrations())) {
          // Integration with subscription service payments is not enabled for service
          $event->getForm()->addError(
            new FormError($this->translator->trans('backoffice.integration.subscription_service.no_integration',
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
      $stmt->execute();
      $results = $stmt->fetchAll();

      if (!empty($results)) {
        $event->getForm()->addError(
          new FormError($this->translator->trans('backoffice.integration.subscription_service.identifier_change_not_allowed', [
            '%payment_identifier%'=> $subscriptionPayment->getPaymentIdentifier()
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
