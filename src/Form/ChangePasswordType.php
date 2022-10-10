<?php

namespace App\Form;

use App\Form\Security\NewPasswordType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Validator\Constraints\UserPassword;
use Symfony\Contracts\Translation\TranslatorInterface;


class ChangePasswordType extends AbstractType
{
  /**
   * @var TranslatorInterface
   */
  private $translator;

  public function __construct(TranslatorInterface $translator)
  {
    $this->translator = $translator;
  }


  /**
   * {@inheritdoc}
   */
  public function buildForm(FormBuilderInterface $builder, array $options)
  {

    $builder
      ->add('currentPassword', PasswordType::class, [
        'label' => 'security.current_password',
        'mapped' => false,
        'constraints' => [new UserPassword(['message' => $this->translator->trans('security.invalid_password')])]
      ])
      ->add(
        'plainPassword',
        NewPasswordType::class,
        [
          'mapped' => false,
          'label' => false
        ]
      );
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
