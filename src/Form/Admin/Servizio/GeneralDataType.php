<?php
namespace App\Form\Admin\Servizio;

use App\Entity\Servizio;
use App\Form\I18n\AbstractI18nType;
use App\Form\I18n\I18nDataMapperInterface;
use App\Form\I18n\I18nTextareaType;
use App\Form\I18n\I18nTextType;
use App\Utils\FormUtils;
use Flagception\Manager\FeatureManagerInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\LessThan;
use Symfony\Contracts\Translation\TranslatorInterface;

class GeneralDataType extends AbstractI18nType
{

  private $featureManager;
  /**
   * @var TranslatorInterface
   */
  private $translator;

  public function __construct(I18nDataMapperInterface $dataMapper, $locale, $locales, FeatureManagerInterface $featureManager, TranslatorInterface $translator)
  {
    parent::__construct($dataMapper, $locale, $locales);
    $this->featureManager = $featureManager;
    $this->translator = $translator;
  }

  public function buildForm(FormBuilderInterface $builder, array $options)
  {

    $statuses = [
      'servizio.statutes.bozza' => Servizio::STATUS_CANCELLED,
      'servizio.statutes.pubblicato' => Servizio::STATUS_AVAILABLE,
      'iscrizioni.status_2' => Servizio::STATUS_SUSPENDED,
      'servizio.statutes.privato' => Servizio::STATUS_PRIVATE,
      'servizio.statutes.schedulato' => Servizio::STATUS_SCHEDULED
    ];

    $accessLevels = [
      'general.anonymous' => Servizio::ACCESS_LEVEL_ANONYMOUS,
      'general.social' => Servizio::ACCESS_LEVEL_SOCIAL,
      'general.level_spid_1' => Servizio::ACCESS_LEVEL_SPID_L1,
      'general.level_spid_2' => Servizio::ACCESS_LEVEL_SPID_L2,
      'general.cie' => Servizio::ACCESS_LEVEL_CIE,
    ];

    $legacyAccessLevels = [
      'general.social' => Servizio::ACCESS_LEVEL_SOCIAL,
      'general.level_spid_1' => Servizio::ACCESS_LEVEL_SPID_L1,
      'general.level_spid_2' => Servizio::ACCESS_LEVEL_SPID_L2,
      'general.cie' => Servizio::ACCESS_LEVEL_CIE,
    ];

    $workflows = [
      'general.approval' => Servizio::WORKFLOW_APPROVAL,
      'general.forwarding' => Servizio::WORKFLOW_FORWARD,
    ];

    /** @var Servizio $servizio */
    $servizio = $builder->getData();


    // you can add the translatable fields
    $this->createTranslatableMapper($builder, $options)
      ->add("name", I18nTextType::class, [
        "label" => 'servizio.nome',
        'attr' => [
          'maxlength' => 255
        ]
      ])
      ->add("compilationInfo", I18nTextareaType::class, [
        "label" => 'servizio.info_compilazione',
        'purify_html' => true,
      ])
      ->add("finalIndications", I18nTextareaType::class, [
        "label" => 'servizio.indicazioni_finali',
        'purify_html' => true,
      ])
    ;

    $builder
      ->add(
        'sticky',
        CheckboxType::class,
        [
          'label' => 'servizio.in_evidenza',
          'required' => false,
        ]
      )
      ->add(
        'status',
        ChoiceType::class,
        [
          'label' => 'servizio.stato',
          'choices' => $statuses,
        ]
      )
      ->add(
        'scheduled_from',
        DateTimeType::class,
        [
          'label' => 'servizio.data_attivazione',
          'required' => false,
          'empty_data' => null,
          'placeholder' => [
            'year' => 'time.year',
            'month' => 'time.month',
            'day' => 'time.day',
            'hour' => 'time.hour',
            'minute' => 'time.minute',
            'second' => 'time.second',
          ],
          'constraints' => [
            new LessThan([
              'propertyPath' => 'parent.all[scheduled_to].data',
              'message' => $this->translator->trans('general.scheduled.from_gte_to')
            ])
          ],
          'label_attr' => ['class' => 'label-datetime-field'],
        ]
      )
      ->add(
        'scheduled_to',
        DateTimeType::class,
        [
          'label' => 'servizio.data_cessazione',
          'required' => false,
          'empty_data' => null,
          'placeholder' => [
            'year' => 'time.year',
            'month' => 'time.month',
            'day' => 'time.day',
            'hour' => 'time.hour',
            'minute' => 'time.minute',
            'second' => 'time.second',
          ],
          'label_attr' => ['class' => 'label-datetime-field'],
        ]
      )
      ->add(
        'service_group',
        EntityType::class,
        [
          'class' => 'App\Entity\ServiceGroup',
          'choice_label' => 'name',
          'label' => 'servizio.gruppo',
          'required' => false,
        ]
      )
      ->add('shared_with_group', CheckboxType::class, [
        'label' => 'servizio.condiviso_con_gruppo',
        'required' => false,
      ])
      ->add('user_groups', EntityType::class,
        [
          'class' => 'App\Entity\UserGroup',
          'choice_label' => 'name',
          'label' => 'user_group.title',
          'required' => false,
          'multiple' => true,
          'attr' => ['style' => 'columns: 3;'],
          'expanded' => true,
          'by_reference' => false
        ]
      )
      ->add(
        'access_level',
        ChoiceType::class,
        [
          'label' => 'servizio.livello_accesso',
          'choices' => $servizio->getPraticaFlowServiceName() == 'ocsdc.form.flow.formio' ? $accessLevels : $legacyAccessLevels,
        ]
      )
      ->add(
        'login_suggested',
        CheckboxType::class,
        [
          'label' => 'servizio.suggerisci_login',
          'required' => false,
        ]
      )->add(
        "post_submit_validation_expression",
        HiddenType::class,
        [
          'required' => false,
        ]
      )->add(
        "post_submit_validation_message",
        HiddenType::class,
        [
          'required' => false,
        ]
      )
      ->add(
        'allow_reopening',
        CheckboxType::class,
        [
          'label' => 'servizio.consenti_riapertura',
          'required' => false,
        ]
      )
      ->add(
        'allow_withdraw',
        CheckboxType::class,
        [
          'label' => 'servizio.consenti_ritiro',
          'required' => false,
        ]
      )
      ->add(
        'allow_integration_request',
        CheckboxType::class,
        [
          'label' => 'servizio.consenti_richiesta_integrazione',
          'required' => false,
        ]
      )
      ->add(
        'workflow',
        ChoiceType::class,
        [
          'label' => 'servizio.flusso',
          'choices' => $workflows,
        ]
      )
      ->add(
        'max_response_time',
        IntegerType::class,
        [
          'label' => 'servizio.max_response_time',
          'required' => false,
          'attr' => array('min' => 1, 'max' => 999)
        ]
      )
      ->add(
        'enable_external_card_url',
        CheckboxType::class,
        [
          'label' => 'servizio.enable_external_card_url',
          'help' => 'servizio.enable_external_card_url_help',
          'required' => false,
          'mapped' => false,
          'data' => !empty($servizio->getExternalCardUrl()),
        ]
      )
      ->add(
        'external_card_url',
        TextType::class,
        [
          'label' => 'servizio.external_card_url',
          'help' => 'servizio.external_card_url_help',
          'required' => false,
        ]
      )
      ->add(
        'satisfyEntrypointId',
        TextType::class,
        [
          'label' => 'ente.satisfy_entrypoint_id',
          'required' => false
        ]
      )
      ;

    if ($this->featureManager->isActive('feature_service_identifier')) {
      $builder->add('identifier', TextType::class, [
        'label' => 'servizio.public_service_identifier',
        'required' => false,
        'disabled' => $servizio->isIdentifierReadonly()
      ]);
    }

    $builder->addEventListener(FormEvents::PRE_SUBMIT, array($this, 'onPreSubmit'));
  }

  public function onPreSubmit(FormEvent $event)
  {
    $data = $event->getData();
    if ($data['status'] == Servizio::STATUS_SCHEDULED) {
      // controllo se almeno uno dei valori delle date inserite è vuoto, se lo è allora tale data è invalida
      $isScheduledFromInvalid = FormUtils::isArrayValueEmpty($data['scheduled_from']);
      $isScheduledToInvalid = FormUtils::isArrayValueEmpty($data['scheduled_to']);

      if ($isScheduledFromInvalid && $isScheduledToInvalid) {
        $event->getForm()->addError(new FormError($this->translator->trans('general.scheduled.from_to_invalid')));
      } else if ($isScheduledFromInvalid || $isScheduledToInvalid) {
        $event->getForm()->addError(new FormError(
          $this->translator->trans($isScheduledFromInvalid ? 'general.scheduled.from_invalid' : 'general.scheduled.to_invalid')
        ));
      }
    } else {
      // se il servizio non è programmato resetto le date di attivazione e cessazione
      foreach (array('scheduled_from', 'scheduled_to') as $key) {
        $data[$key]['date']['day'] = "";
        $data[$key]['date']['month'] = "";
        $data[$key]['date']['year'] = "";
        $data[$key]['time']['hour'] = "";
        $data[$key]['time']['minute'] = "";
      }
      $event->setData($data);
    }
  }

  public function configureOptions(OptionsResolver $resolver)
  {
    $resolver->setDefaults(array(
      'data_class' => 'App\Entity\Servizio'
    ));

    $this->configureTranslationOptions($resolver);
  }

  public function getBlockPrefix()
  {
    return 'general_data';
  }
}
