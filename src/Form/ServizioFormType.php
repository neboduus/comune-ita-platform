<?php

namespace App\Form;

use App\Dto\ServiceDto;
use App\Entity\Pratica;
use App\Entity\Servizio;
use App\Helpers\EventTaxonomy;
use App\Model\FeedbackMessage;
use App\Model\Service;
use App\Services\Manager\ServiceManager;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\LessThan;
use Symfony\Contracts\Translation\TranslatorInterface;

class ServizioFormType extends AbstractType
{
  /**
   * @var ServiceManager
   */
  private $serviceManager;

  private $defaultLocale;

  /**
   * @var TranslatorInterface
   */
  private $translator;

  public function __construct(ServiceManager $serviceManager, $defaultLocale, TranslatorInterface $translator)
  {
    $this->serviceManager = $serviceManager;
    $this->defaultLocale = $defaultLocale;
    $this->translator = $translator;
  }

  /**
   * @param FormBuilderInterface $builder
   * @param array $options
   */
  public function buildForm(FormBuilderInterface $builder, array $options)
  {
    $builder
      ->add('name')
      ->add('tenant', EntityType::class, [
        'class' => 'App\Entity\Ente',
        'choice_label' => 'name',
      ])
      ->add('topics')
      ->add('description')
      ->add('short_description')
      ->add('howto')
      ->add('how_to_do')
      ->add('what_you_need')
      ->add('what_you_get')
      ->add('costs')
      ->add('who')
      ->add('special_cases')
      ->add('more_info')
      ->add('constraints')
      ->add('conditions')
      ->add('times_and_deadlines')
      ->add('booking_call_to_action')
      ->add('compilation_info')
      ->add('life_events', ChoiceType::class, [
        'choices' => EventTaxonomy::LIFE_EVENTS,
        'multiple' => true,
        'required' => false,
      ])
      ->add('business_events', ChoiceType::class, [
        'choices' => EventTaxonomy::BUSINESS_EVENTS,
        'multiple' => true,
        'required' => false,
      ])
      ->add('final_indications', TextareaType::class, [
        "label" => false,
        'empty_data' => 'La domanda è stata correttamente registrata, non ti sono richieste altre operazioni. Grazie per la tua collaborazione.',
      ])
      ->add('coverage', CollectionType::class, [
        'entry_type' => TextType::class,
        "allow_add" => true,
        "allow_delete" => true,
        'prototype' => true,
        "label" => false
      ])
      ->add('response_type')
      ->add('flow_steps', CollectionType::class, [
        'entry_type' => FlowStepType::class,
        "allow_add" => true,
        "allow_delete" => true,
        'prototype' => true,
        "label" => false
      ])
      ->add('protocol_required')
      ->add('protocol_handler')
      ->add('protocollo_parameters', TextareaType::class, ['empty_data' => ''])
      ->add('payment_required')
      ->add('payment_parameters', PaymentParametersType::class, [
        'data_class' => null
      ])
      ->add('integrations', IntegrationsType::class)
      ->add('io_parameters', IOServiceParametersType::class, [
        'required' => false,
        'data_class' => null
      ])
      ->add('sticky')
      ->add('status')
      ->add('access_level')
      ->add('login_suggested')
      ->add('scheduled_from', DateTimeType::class, [
        'widget' => 'single_text',
        'required' => false,
        'constraints' => [
          new LessThan([
            'propertyPath' => 'parent.all[scheduled_to].data',
            'message' => $this->translator->trans('general.scheduled.from_gte_to')
          ])
        ],
      ])
      ->add('scheduled_to', DateTimeType::class, [
        'widget' => 'single_text',
        'required' => false,
      ])
      ->add('service_group')
      ->add('shared_with_group')
      ->add('user_group_ids', CollectionType::class, [
        'entry_type' => TextType::class,
        "allow_add" => true,
        "allow_delete" => true,
        'prototype' => true,
        "label" => false
      ])
      ->add('allow_reopening')
      ->add('allow_withdraw')
      ->add('allow_integration_request', CheckboxType::class, [
          'required' => false,
        ]
      )
      ->add('workflow')
      ->add('max_response_time')
      ->add('recipients', CollectionType::class, [
        'entry_type' => TextType::class,
        "allow_add" => true,
        "allow_delete" => true,
        'prototype' => true,
        "label" => false
      ])
      ->add('recipients_id', CollectionType::class, [
        'entry_type' => TextType::class,
        "allow_add" => true,
        "allow_delete" => true,
        'prototype' => true,
        "label" => false
      ])
      ->add('geographic_areas', CollectionType::class, [
        'entry_type' => TextType::class,
        "allow_add" => true,
        "allow_delete" => true,
        'prototype' => true,
        "label" => false
      ])
      ->add('geographic_areas_id', CollectionType::class, [
        'entry_type' => TextType::class,
        "allow_add" => true,
        "allow_delete" => true,
        'prototype' => true,
        "label" => false
      ])
      ->add('allow_withdraw')
      ->add('external_card_url')
      ->add('feedback_messages', FeedbackMessagesFormType::class)
      ->add('satisfy_entrypoint_id', TextType::class, [
          'label' => false,
          'required' => false
      ])
    ;

    $builder->addEventListener(FormEvents::PRE_SUBMIT, array($this, 'onPreSubmit'));
  }

  public function onPreSubmit(FormEvent $event)
  {
    /** @var Service $service */
    $service = $event->getForm()->getData();
    $data = $event->getData();
    $locale = $event->getForm()->getConfig()->getOption('locale');

    if (isset($data['integrations']['trigger']) && $data['integrations']['trigger']) {
      $service->setIntegrations($data['integrations']);
    } else {
      // No integration needed
      $service->setIntegrations([]);
    }

    // Set default feedback messages for current locale if service has no feedback messages
    if (!$service->getFeedbackMessages()) {
      $service->setFeedbackMessages(ServiceDto::decorateFeedbackMessages($this->serviceManager->getDefaultFeedbackMessages()[$locale]));
    }

    $feedbackMessagesStatuses = array_keys(FeedbackMessage::STATUS_NAMES);
    foreach ($feedbackMessagesStatuses as $status) {
      $statusName = strtolower(Pratica::getStatusNameByCode($status));
      if (!isset($data['feedback_messages'][$statusName])) {
        // Update missing feedback messages in form data
        $data['feedback_messages'][$statusName] = $service->getFeedbackMessages()->getMessageByStatusCode($status)->jsonSerialize();
      }
    }

    // se mando un payload con data di attivazione/cessazione e il servizio non è programmato restituisco un errore
    $status = isset($data['status']) ? $data['status'] : $service->getStatus();
    if ($status != Servizio::STATUS_SCHEDULED && (isset($data['scheduled_from']) && $data['scheduled_from'] || isset($data['scheduled_to']) && $data['scheduled_to'])) {
      $event->getForm()->addError(new FormError($this->translator->trans('general.scheduled.from_to_not_null')));
    }

    // se mando un payload con data di attivazione/cessazione non valorizzati e il servizio è programmato restituisco un errore
    if (isset($data['status']) && $data['status'] == Servizio::STATUS_SCHEDULED && (!isset($data['scheduled_from']) || !isset($data['scheduled_to']))) {
      $event->getForm()->addError(new FormError($this->translator->trans('general.scheduled.from_to_null')));
    }

    // se un servizio non è programmato, le date di attivazione/cessazione devono sempre essere nulle
    if ($status != Servizio::STATUS_SCHEDULED) {
      $service->setScheduledFrom(null);
      $service->setScheduledTo(null);
    }

    $event->setData($data);
    $event->getForm()->setData($service);

  }

  /**
   * @param OptionsResolver $resolver
   */
  public function configureOptions(OptionsResolver $resolver)
  {
    $resolver->setDefaults(array(
      'data_class' => 'App\Model\Service',
      'allow_extra_fields' => true,
      'csrf_protection' => false,
      'locale' => $this->defaultLocale
    ));
  }

}
