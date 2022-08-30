<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Validator\Constraints\UserPassword;


class ProfileUserType extends AbstractType
{
  /**
   * {@inheritdoc}
   */
  public function buildForm(FormBuilderInterface $builder, array $options)
  {

    $builder
      ->add('username', TextType::class, [
        'label' => 'general.username',
        'required' => true
      ])
      ->add('email', EmailType::class, [
        'label' => 'general.email',
        'required' => true
      ])
      ->add('plainPassword', PasswordType::class, [
        'label' => 'security.current_password',
        'mapped' => false,
        'constraints' => [new UserPassword()]
        ]);
  }

    /**
   * {@inheritdoc}
   */
  public function configureOptions(OptionsResolver $resolver)
  {
    $resolver->setDefaults(array(
      'data_class' => 'App\Entity\User'
    ));
  }


}
