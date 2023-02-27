<?php

namespace App\Form;

use App\Dto\Operator;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OperatorAPIType extends AbstractType
{
  /**
   * @param FormBuilderInterface $builder
   * @param array $options
   */
  public function buildForm(FormBuilderInterface $builder, array $options)
  {
    /** @var Operator $operator */
    $operator = $builder->getData();

    $builder
      ->add('username', TextType::class)
      ->add('nome', TextType::class)
      ->add('cognome', TextType::class)
      ->add('cellulare', TextType::class, [
        'required' => false
      ])
      ->add('email', EmailType::class, [
        'required' => !$operator->isSystemUser()
      ]);
  }

  /**
   * @param OptionsResolver $resolver
   */
  public function configureOptions(OptionsResolver $resolver)
  {
    $resolver->setDefaults(array(
      'data_class' => 'App\Dto\Operator',
      'csrf_protection' => false,
      'allow_extra_fields' => true
    ));
  }

}
