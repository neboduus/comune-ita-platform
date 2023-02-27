<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserAPIType extends AbstractType
{
  /**
   * @param FormBuilderInterface $builder
   * @param array $options
   */
  public function buildForm(FormBuilderInterface $builder, array $options)
  {
    $builder
      ->add('codice_fiscale', TextType::class, [
        'required' => true
      ])
      ->add('nome', TextType::class, [
        'required' => false
      ])
      ->add('cognome', TextType::class, [
        'required' => false
      ])
      ->add('data_nascita', DateType::class, [
        'widget' => 'single_text',
        'required' => false,
        'label_attr' => ['class' => 'active'],
        'empty_data' => ''
      ])
      ->add('luogo_nascita')
      ->add('codice_nascita')
      ->add('provincia_nascita')
      ->add('stato_nascita')
      ->add('sesso')
      ->add('telefono')
      ->add('cellulare')
      ->add('email')
      ->add('indirizzo_domicilio')
      ->add('cap_domicilio')
      ->add('citta_domicilio')
      ->add('provincia_domicilio')
      ->add('stato_domicilio')
      ->add('indirizzo_residenza')
      ->add('cap_residenza')
      ->add('citta_residenza')
      ->add('provincia_residenza')
      ->add('stato_residenza');
  }

  /**
   * @param OptionsResolver $resolver
   */
  public function configureOptions(OptionsResolver $resolver)
  {
    $resolver->setDefaults(array(
      'data_class' => 'App\Dto\User',
      'csrf_protection' => false,
      'allow_extra_fields' => true
    ));
  }

}
