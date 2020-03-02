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
      'Social'         => Servizio::ACCESS_LEVEL_SOCIAL,
      'Spid livello 1' => Servizio::ACCESS_LEVEL_SPID_L1,
      'Spid livello 2' => Servizio::ACCESS_LEVEL_SPID_L2,
      'Cie'            => Servizio::ACCESS_LEVEL_CIE,
    ];

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
      ->add('sticky')
      ->add('sticky', CheckboxType::class, [
        'label' => 'In evidenza?',
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
