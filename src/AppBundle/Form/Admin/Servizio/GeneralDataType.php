<?php


namespace AppBundle\Form\Admin\Servizio;


use AppBundle\Entity\Servizio;
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

class GeneralDataType extends AbstractType
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

    $workflows = [
      'Approvazione' => Servizio::WORKFLOW_APPROVAL,
      'Inoltro' => Servizio::WORKFLOW_FORWARD
    ];

    /** @var Servizio $servizio */
    $servizio = $builder->getData();

    $builder->add(
      "name", TextType::class, [
      "label" => 'Nome del servizio',
      "required" => true,
    ])
      ->add('topics', EntityType::class, [
        'class' => 'AppBundle\Entity\Categoria',
        'choice_label' => 'name',
        'label' => 'Categoria'
      ])
      ->add('description', TextareaType::class, [
        'label' => "Cos'è",
        'required' => false
      ])
      ->add('who', TextareaType::class, [
        'label' => 'A chi si rivolge',
        'required' => false
      ])
      ->add('coverage', TextType::class, [
        'label' => 'Copertura geografica - (se più di uno inserire i valori separati da virgola)',
        'data' => is_array($servizio->getCoverage()) ? implode(',', $servizio->getCoverage()) : $servizio->getCoverage(),
        'required' => false
      ])
      ->add('howto', TextareaType::class, [
        'label' => 'Accedere al servizio',
        'required' => false
      ])
      ->add('special_cases', TextareaType::class, [
        'label' => 'Casi particolari',
        'required' => false
      ])
      ->add('more_info', TextareaType::class, [
        'label' => 'Maggiori informazioni',
        'required' => false
      ])
      ->add('compilation_info', TextareaType::class, [
        'label' => 'Informazioni visualizzate durante la compilazione del servizio',
        'required' => false
      ])
      ->add('final_indications', TextareaType::class, [
        'label' => 'Indicazioni mostrate al termine della compilazione del servizio',
        'required' => false,
        //'empty_data' => 'La domanda è stata correttamente registrata, non ti sono richieste altre operazioni. Grazie per la tua collaborazione.',
      ])
      ->add('sticky', CheckboxType::class, [
        'label' => 'In evidenza?',
        'required' => false
      ])
      ->add('status', ChoiceType::class, [
        'label' => 'Stato',
        'choices' => $statuses
      ])
      ->add('status', ChoiceType::class, [
        'label' => 'Stato',
        'choices' => $statuses
      ])
      ->add('scheduled_from', DateTimeType::class, [
        'label' => 'Data di attivazione del servizio',
        'required' => false,
        'empty_data' => null,
        'placeholder' => [
          'year' => 'Anno', 'month' => 'Mese', 'day' => 'Giorno',
          'hour' => 'Ora', 'minute' => 'Minuto', 'second' => 'Secondo',
        ],
        'label_attr' => ['class' => 'label-datetime-field']
      ])
      ->add('scheduled_to', DateTimeType::class, [
        'label' => 'Data di cessazione del servizio',
        'required' => false,
        'empty_data' => null,
        'placeholder' => [
          'year' => 'Anno', 'month' => 'Mese', 'day' => 'Giorno',
          'hour' => 'Ora', 'minute' => 'Minuto', 'second' => 'Secondo',
        ],
        'label_attr' => ['class' => 'label-datetime-field']
      ])
      ->add('service_group', EntityType::class, [
        'class' => 'AppBundle\Entity\ServiceGroup',
        'choice_label' => 'name',
        'label' => 'Gruppo di servizi',
        'required' => false
      ])
      ->add('access_level', ChoiceType::class, [
        'label' => 'Livello di accesso al servizio',
        'choices' => $accessLevels
      ])
      ->add('login_suggested', CheckboxType::class, [
        'label' => 'Suggerisci il Login per l\'autocompletamento?',
        'required' => false
      ])->add(
        "post_submit_validation_expression", HiddenType::class, [
        'required' => false
      ])->add(
        "post_submit_validation_message", HiddenType::class, [
        'required' => false
      ])
      ->add('allow_reopening', CheckboxType::class, [
        'label' => 'Consenti di riprendere in carico le pratiche accettate o rifiutate',
        'required' => false,

      ])
      ->add('workflow', ChoiceType::class, [
        'label' => 'Flusso di lavoro',
        'choices' => $workflows
      ]);
  }

  public function getBlockPrefix()
  {
    return 'general_data';
  }
}
