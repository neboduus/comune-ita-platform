<?php


namespace AppBundle\Form\Admin\Servizio;


use AppBundle\Entity\Servizio;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class GeneralDataType extends AbstractType
{
  public function buildForm(FormBuilderInterface $builder, array $options)
  {

    $statuses = [
      'Bozza'      => Servizio::STATUS_CANCELLED,
      'Pubblicato' => Servizio::STATUS_AVAILABLE,
      'Sospeso'    => Servizio::STATUS_SUSPENDED
    ];

    $accessLevels = [
      'Anonimo'        => Servizio::ACCESS_LEVEL_ANONYMOUS,
      'Anonimo con possibilità di Login'        => Servizio::ACCESS_LEVEL_ANONYMOUS_WITH_LOGIN,
      'Social'         => Servizio::ACCESS_LEVEL_SOCIAL,
      'Spid livello 1' => Servizio::ACCESS_LEVEL_SPID_L1,
      'Spid livello 2' => Servizio::ACCESS_LEVEL_SPID_L2,
      'Cie'            => Servizio::ACCESS_LEVEL_CIE,
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
        'label' => 'Maggiori informazioni visualizzate durante la fase di compilazione',
        'required' => false
      ])
      ->add('completed_info', TextareaType::class, [
        'label' => 'Maggiori informazioni visualizzate al termine della fase di compilazione',
        'required' => false
      ])
      ->add('email_message', TextareaType::class, [
        'label' => 'Messaggio inviato al cittadino via email',
        'required' => false
      ])
      ->add('sticky', CheckboxType::class, [
        'label' => 'In evidenza?',
        'required' => false
      ])
      ->add('protocol_required', CheckboxType::class, [
        'label' => 'Protocollazione richiesta?',
        'required' => false
      ])
      ->add('status', ChoiceType::class, [
        'label' => 'Stato',
        'choices' => $statuses
      ])
      ->add('access_level', ChoiceType::class, [
        'label' => 'Livello di accesso al servizio',
        'choices' => $accessLevels
      ]);
  }

  public function getBlockPrefix()
  {
    return 'general_data';
  }
}
