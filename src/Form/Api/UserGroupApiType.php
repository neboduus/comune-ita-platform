<?php

namespace App\Form\Api;

use App\Entity\UserGroup;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserGroupApiType extends AbstractType
{

  public function buildForm(FormBuilderInterface $builder, array $options)
  {
    $builder
      ->add('name', TextType::class, [
        'required' => true,
      ])
      ->add('short_description', TextareaType::class, [
        'required' => false,
        'purify_html' => true,
      ])
      ->add('main_function', TextareaType::class, [
        'required' => false,
        'purify_html' => true,
      ])
      ->add('more_info', TextareaType::class, [
        'label' => 'user_group.more_info',
        'required' => false,
        'purify_html' => true,
      ])
      ->add('topic', EntityType::class, [
        'class' => 'App\Entity\Categoria',
        'label' => 'servizio.categoria',
        'choice_label' => 'name',
        'required' => false,
      ])
      ->add('manager', EntityType::class, [
        'class' => 'App\Entity\OperatoreUser',
        'label' => 'user_group.manager',
        'choice_label' => 'fullname',
        'required' => false
      ])
      ->add('core_contact_point', ContactPointApiType::class, [
        'required' => false,
        'label' => 'user_group.core_contact_point',
      ])
    ;
  }

  /**
   * {@inheritdoc}
   */
  public function configureOptions(OptionsResolver $resolver)
  {
    $resolver->setDefaults(array(
      'data_class' => UserGroup::class,
      'allow_extra_fields' => true,
      'csrf_protection' => false,
    ));
  }
}
