<?php


namespace AppBundle\Form\Admin\ServiceGroup;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;


class ServiceGroupType extends AbstractType
{
  public function buildForm(FormBuilderInterface $builder, array $options)
  {

    $builder
      ->add('name', TextType::class, [
        'label' => 'Nome'
      ])
      ->add('description', TextareaType::class, [
        'label' => 'Descrizione',
        'required' => false
      ])
      ->add('sticky', CheckboxType::class, [
        'label' => 'In evidenza?',
        'required' => false,
      ])
      ->add('register_in_folder', CheckboxType::class, [
        'label' => 'Protocollare all\'interno dello stesso fascicolo?',
        'required' => false
      ]);
  }
}
