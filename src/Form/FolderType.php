<?php

namespace App\Form;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FolderType extends AbstractType
{
  public function buildForm(FormBuilderInterface $builder, array $options)
  {
    $builder
      ->add('title', TextType::class, [
        'label' => 'titolo',
        'required' => true
      ])
      ->add('description', TextareaType::class, [
        'label' => 'descrizione',
        'required' => false
      ])
      ->add('owner', EntityType::class, [
        'class' => 'App\Entity\CPSUser',
        'label' => 'proprietario',
        'required' => true
      ])
      ->add('correlated_services', EntityType::class, [
        'class' => 'App\Entity\Servizio',
        'label' => 'Servizi correlati',
        'multiple' => true,
      ]);
  }

  public function configureOptions(OptionsResolver $resolver)
  {
    $resolver->setDefaults(array(
      'data_class' => 'App\Entity\Folder',
      'csrf_protection' => false
    ));
  }

  public function getBlockPrefix()
  {
    return 'app_bundle_folder';
  }
}
