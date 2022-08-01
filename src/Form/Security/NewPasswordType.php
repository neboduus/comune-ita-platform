<?php

namespace App\Form\Security;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

class NewPasswordType extends AbstractType
{
  /**
   * @var TranslatorInterface
   */
  private $translator;

  public function __construct(TranslatorInterface $translator)
  {
    $this->translator = $translator;
  }

  public function buildForm(FormBuilderInterface $builder, array $options)
  {
    $builder
      ->add(
        'plainPassword',
        RepeatedType::class,
        [
          'type' => PasswordType::class,
          'mapped' => false,
          'invalid_message' => $this->translator->trans('security.non_matching_password'),
          'attr' => [
            'class' => 'form-control',
            'placeholder' => 'password',
          ],
          'required' => true,
          'first_options' => ['label' => 'security.new_password'],
          'second_options' => ['label' => 'security.repeat_password'],
          'constraints' => [
            new NotBlank(
              [
                'message' => $this->translator->trans('security.password_required'),
              ]
            ),
            new Length(
              [
                'min' => 8,
                'minMessage' => $this->translator->trans('security.password_min_length'),
                // max length allowed by Symfony for security reasons
                'max' => 4096,
              ]
            ),
            new Regex([
              'pattern' => '/[0-9]{1,}/',
              'message' => $this->translator->trans('security.password_number_check')
            ]),
            new Regex([
              'pattern' => '/[@$!%*#?&]{1,}/',
              'message' => $this->translator->trans('security.password_special_check')
            ]),
            new Regex([
              'pattern' => '/[A-Z]{1,}/',
              'message' => $this->translator->trans('security.password_upper_check')
            ])
          ],
        ]
      );
  }

  /**
   * @param OptionsResolver $resolver
   */
  public function configureOptions(OptionsResolver $resolver)
  {
    $resolver->setDefaults(array(
      'csrf_protection' => false
    ));
  }
}
