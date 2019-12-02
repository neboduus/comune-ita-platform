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
      'Cancellato' => Servizio::STATUS_CANCELLED,
      'Pubblicato' => Servizio::STATUS_AVAILABLE,
      'Sospeso'    => Servizio::STATUS_SUSPENDED
    ];

    $builder->add(
      "name",
      TextType::class,
      [
        "label" => 'Nome del servizio',
        "required" => true,
      ]
    )
      ->add('topics', EntityType::class, [
        'class' => 'AppBundle\Entity\Categoria',
        'choice_label' => 'name',
      ])
      ->add('description')
      ->add('howto')
      ->add('who')
      ->add('special_cases')
      ->add('more_info')
      /*->add('coverage', CollectionType::class, [
        'entry_type' => TextType::class,
        "entry_options" => ["label" => 'aaaa'],
        'allow_add' => true,
        'prototype' => true
      ])*/
      ->add('sticky')
      ->add('status', ChoiceType::class, ['choices' => $statuses]);
  }

  public function getBlockPrefix()
  {
    return 'general_data';
  }
}
