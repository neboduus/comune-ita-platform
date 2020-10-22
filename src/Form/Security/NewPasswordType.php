<?php

namespace App\Form\Security;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class NewPasswordType extends AbstractType
{
  public function buildForm(FormBuilderInterface $builder, array $options)
  {
    $builder
      ->add(
        'plainPassword',
        RepeatedType::class,
        [
          'type' => PasswordType::class,
          'mapped' => false,
          'invalid_message' => 'Password non valida',
          'attr' => [
            'class' => 'form-control',
            'placeholder' => 'password',
          ],
          'required' => true,
          'first_options' => ['label' => 'Password'],
          'second_options' => ['label' => 'Conferma password'],
          'constraints' => [
            new NotBlank(
              [
                'message' => 'Password richiesta',
              ]
            ),
            new Length(
              [
                'min' => 6,
                'minMessage' => 'Inserisci almeno 6 caratteri',
                // max length allowed by Symfony for security reasons
                'max' => 4096,
              ]
            ),
          ],
        ]
      );
  }
}
