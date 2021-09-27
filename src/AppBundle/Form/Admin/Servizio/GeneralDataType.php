<?php


namespace AppBundle\Form\Admin\Servizio;


use AppBundle\Entity\Servizio;
use AppBundle\Form\I18n\AbstractI18nType;
use AppBundle\Form\I18n\I18nTextareaType;
use AppBundle\Form\I18n\I18nTextType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class GeneralDataType extends AbstractI18nType
{
  public function buildForm(FormBuilderInterface $builder, array $options)
  {

    $statuses = [
      'Bozza' => Servizio::STATUS_CANCELLED,
      'Pubblicato' => Servizio::STATUS_AVAILABLE,
      'Non attivo' => Servizio::STATUS_SUSPENDED,
      'Privato' => Servizio::STATUS_PRIVATE,
      'Programmato' => Servizio::STATUS_SCHEDULED,
    ];

    $accessLevels = [
      'Anonimo' => Servizio::ACCESS_LEVEL_ANONYMOUS,
      'Social' => Servizio::ACCESS_LEVEL_SOCIAL,
      'Spid livello 1' => Servizio::ACCESS_LEVEL_SPID_L1,
      'Spid livello 2' => Servizio::ACCESS_LEVEL_SPID_L2,
      'Cie' => Servizio::ACCESS_LEVEL_CIE,
    ];

    $legacyAccessLevels = [
      'Social' => Servizio::ACCESS_LEVEL_SOCIAL,
      'Spid livello 1' => Servizio::ACCESS_LEVEL_SPID_L1,
      'Spid livello 2' => Servizio::ACCESS_LEVEL_SPID_L2,
      'Cie' => Servizio::ACCESS_LEVEL_CIE,
    ];

    $workflows = [
      'Approvazione' => Servizio::WORKFLOW_APPROVAL,
      'Inoltro' => Servizio::WORKFLOW_FORWARD,
    ];

    /** @var Servizio $servizio */
    $servizio = $builder->getData();


    // you can add the translatable fields
    $this->createTranslatableMapper($builder, $options)
      ->add("name", I18nTextType::class, [
        "label" => 'servizio.nome'
      ])
      ->add("description", I18nTextareaType::class, [
        "label" => 'servizio.cos_e'
      ])
      ->add("who", I18nTextareaType::class, [
        "label" => 'servizio.a_chi_si_rivolge'
      ])
      ->add("howto", I18nTextareaType::class, [
        "label" => 'servizio.accedere'
      ])
      ->add("specialCases", I18nTextareaType::class, [
        "label" => 'servizio.casi_particolari'
      ])
      ->add("moreInfo", I18nTextareaType::class, [
        "label" => 'servizio.maggiori_info'
      ])
      ->add("compilationInfo", I18nTextareaType::class, [
        "label" => 'servizio.info_compilazione'
      ])
      ->add("finalIndications", I18nTextareaType::class, [
        "label" => 'servizio.indicazioni_finali'
      ])
    ;

    $builder
      ->add(
        'topics',
        EntityType::class,
        [
          'class' => 'AppBundle\Entity\Categoria',
          'choice_label' => 'name',
          'label' => 'servizio.categoria',
        ]
      )
      ->add(
        'coverage',
        TextType::class,
        [
          'label' => 'servizio.copertura_helper',
          'data' => is_array($servizio->getCoverage()) ? implode(
            ',',
            $servizio->getCoverage()
          ) : $servizio->getCoverage(),
          'required' => false,
        ]
      )
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
            'year' => 'Anno',
            'month' => 'Mese',
            'day' => 'Giorno',
            'hour' => 'Ora',
            'minute' => 'Minuto',
            'second' => 'Secondo',
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
            'year' => 'Anno',
            'month' => 'Mese',
            'day' => 'Giorno',
            'hour' => 'Ora',
            'minute' => 'Minuto',
            'second' => 'Secondo',
          ],
          'label_attr' => ['class' => 'label-datetime-field'],
        ]
      )
      ->add(
        'service_group',
        EntityType::class,
        [
          'class' => 'AppBundle\Entity\ServiceGroup',
          'choice_label' => 'name',
          'label' => 'servizio.gruppo',
          'required' => false,
        ]
      )
      ->add('shared_with_group', CheckboxType::class, [
        'label' => 'servizio.condiviso_con_gruppo',
        'required' => false,
      ])
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
        'workflow',
        ChoiceType::class,
        [
          'label' => 'servizio.flusso',
          'choices' => $workflows,
        ]
      );
  }

  public function configureOptions(OptionsResolver $resolver)
  {
    $resolver->setDefaults(array(
      'data_class' => 'AppBundle\Entity\Servizio'
    ));

    $this->configureTranslationOptions($resolver);
  }

  public function getBlockPrefix()
  {
    return 'general_data';
  }
}
